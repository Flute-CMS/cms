<?php

namespace Flute\Core\Modules\Auth\Services\Concerns;

use DateTimeImmutable;
use Exception;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\Exceptions\NeedRegistrationException;
use Flute\Core\Modules\Auth\Events\UserRegisteredEvent;
use Flute\Core\Services\DiscordService;
use Hybridauth\User\Profile;
use Throwable;

trait HandlesSocialAuth
{
    /**
     * @throws Exception
     */
    public function authenticateWithRegister(string $providerName): User
    {
        $social = $this->retrieveSocialNetwork($this->normalizeProviderName($providerName));

        $this->ensureRegistrationAllowed($social);

        $authData = $this->authenticate($providerName);

        $authData['adapter']->disconnect();

        try {
            $authData['adapter']->getStorage()->clear();
        } catch (Throwable $e) {
            logs()->warning($e);
        }

        $existingUser = $this->findUserBySocialProfile($authData['profile']);

        if ($existingUser) {
            $this->updateAvatarFromProfileIfNeeded($existingUser, $authData['profile']);
            $this->updateBannerFromProfileIfNeeded($existingUser, $authData['profile']);

            return $existingUser;
        }

        if ($this->requiresAdditionalRegistration()) {
            throw new NeedRegistrationException($authData['profile'], $social['entity']);
        }

        return $this->registerNewUser($authData['profile'], $social['entity']);
    }

    /**
     * @throws Exception
     */
    public function authenticate(string $providerName, bool $bind = false): array
    {
        $this->initializeHybridAuth($providerName, $bind);

        try {
            $adapter = $this->hybridauth->authenticate($this->normalizeProviderName($providerName));
            $userProfile = $adapter->getUserProfile();
        } catch (\Hybridauth\Exception\UnexpectedApiResponseException $e) {
            try {
                if (isset($adapter) && method_exists($adapter, 'getStorage')) {
                    $adapter->getStorage()->clear();
                }
            } catch (Throwable $inner) {
                logs()->warning($inner);
            }

            try {
                if (isset($adapter) && method_exists($adapter, 'disconnect')) {
                    $adapter->disconnect();
                }
            } catch (Throwable $inner) {
                logs()->warning($inner);
            }

            logs()->warning('Social authenticate UnexpectedApiResponseException: ' . $e->getMessage());

            throw new Exception('Failed to load social profile. Please try again.');
        }

        $this->clearAuthData();

        if (!$userProfile) {
            throw new Exception('Failed to load user profile.');
        }

        return [
            'profile' => $userProfile,
            'adapter' => $adapter,
        ];
    }

    public function registerNewUser(Profile $userProfile, SocialNetwork $socialNetwork): User
    {
        $email = $userProfile->email;

        if ($email) {
            $existingUser = User::query()->where(['email' => $email])->fetchOne();

            if ($existingUser) {
                $email = null;
            }
        }

        $avatarPath = $userProfile->photoURL ?? config('profile.default_avatar');

        $this->findAndDeleteTemporaryUser($socialNetwork->key, $userProfile->identifier);

        $user = new User();
        $user->name = mb_substr($userProfile->displayName, 0, 255);
        $user->email = $email;
        $user->uri = null;
        $user->login = null;
        $user->avatar = $avatarPath;
        $user->verified = true;

        $userSocialNetwork = new UserSocialNetwork();
        $userSocialNetwork->value = $userProfile->identifier;
        $userSocialNetwork->url = $userProfile->profileURL;
        $userSocialNetwork->name = $userProfile->displayName;
        $userSocialNetwork->user = $user;
        $userSocialNetwork->socialNetwork = $socialNetwork;
        $userSocialNetwork->linkedAt = new DateTimeImmutable();

        if ($socialNetwork->key === 'Discord' && !empty($userSocialNetwork->value)) {
            $userSocialNetwork->url = 'https://discord.com/users/' . $userSocialNetwork->value;
        }

        $additionalData = [];
        if (!empty($userProfile->photoURL)) {
            $additionalData['photoUrl'] = $userProfile->photoURL;
        }
        if (!empty($userProfile->data) && is_array($userProfile->data)) {
            $additionalData = array_merge($additionalData, $userProfile->data);
        }
        if (!empty($additionalData)) {
            $userSocialNetwork->setAdditional($additionalData);
        }

        try {
            transaction([$user, $userSocialNetwork])->run();
        } catch (\Cycle\Database\Exception\StatementException\ConstrainException $e) {
            logs()->warning($e);

            $existingUser = $this->findUserBySocialProfile($userProfile);
            if ($existingUser) {
                return $existingUser;
            }

            throw $e;
        }

        if ($socialNetwork->key === 'Telegram') {
            $extra = $userSocialNetwork->getAdditional() ?? [];
            $extra['user_id'] = $user->id;
            $userSocialNetwork->setAdditional($extra);
            $userSocialNetwork->save();
        }

        events()->dispatch(new UserRegisteredEvent($user), UserRegisteredEvent::NAME);

        if ($socialNetwork->key === 'Discord') {
            app()->get(DiscordService::class)->linkRoles($user, $user->roles);
        }

        $this->updateBannerFromProfileIfNeeded($user, $userProfile);

        return $user;
    }

    /**
     * @throws Exception
     */
    public function bindSocialNetwork(User $user, string $socialNetworkName): void
    {
        $normalized = $this->normalizeProviderName($socialNetworkName);
        $authData = $this->authenticate($normalized, true);
        $social = $this->retrieveSocialNetwork($normalized);

        $socialNetworkEntity = SocialNetwork::findByPK($social['entity']->id);

        $userSocialNetwork = UserSocialNetwork::query()
            ->where([
                'user.id' => $user->id,
                'socialNetwork.id' => $social['entity']->id,
                'user.isTemporary' => false,
            ])
            ->fetchOne();

        $profile = $authData['profile'];
        $token = $authData['adapter']->getAccessToken();

        $existingSocials = UserSocialNetwork::query()
            ->where([
                'value' => $profile->identifier,
                'socialNetwork.id' => $social['entity']->id,
            ])
            ->fetchAll();

        foreach ($existingSocials as $existingSocial) {
            $existingUser = null;
            try {
                $existingUser = $existingSocial->user;
            } catch (Throwable $e) {
            }

            if ($existingUser && $existingUser->id === $user->id) {
                continue;
            }

            if (!$existingUser || $existingUser->isTemporary()) {
                try {
                    transaction($existingSocial, 'delete')->run();
                    if ($existingUser) {
                        transaction($existingUser, 'delete')->run();
                    }
                } catch (Throwable $e) {
                    logs()->error('Error deleting orphaned social binding: ' . $e->getMessage());

                    throw new Exception('Failed to reassign social account. Please try again.');
                }
            } else {
                throw new Exception('This social account is already linked to another user.');
            }
        }
        $now = new DateTimeImmutable();

        if ($userSocialNetwork) {
            $lastLinked = $userSocialNetwork->linkedAt;

            if (
                $social['entity']->cooldownTime > 0
                && $lastLinked
                && ( $now->getTimestamp() - $lastLinked->getTimestamp() ) < $social['entity']->cooldownTime
            ) {
                throw new Exception(__('profile.errors.social_delay'));
            }

            $userSocialNetwork->value = $profile->identifier;
            $userSocialNetwork->url = $profile->profileURL;
            $userSocialNetwork->name = $profile->displayName;
            $userSocialNetwork->linkedAt = new DateTimeImmutable();

            if ($social['entity']->key === 'Discord' && !empty($userSocialNetwork->value)) {
                $userSocialNetwork->url = 'https://discord.com/users/' . $userSocialNetwork->value;
            }

            $additionalData = $token ? json_decode(json_encode($token), true) : [];
            if (!empty($profile->photoURL)) {
                $additionalData['photoUrl'] = $profile->photoURL;
            }
            if (!empty($profile->data) && is_array($profile->data)) {
                $additionalData = array_merge($additionalData, $profile->data);
            }
            if ($social['entity']->key === 'Telegram') {
                $additionalData['user_id'] = $user->id;
            }
            if (!empty($additionalData)) {
                $userSocialNetwork->additional = json_encode($additionalData);
            }

            try {
                transaction($userSocialNetwork)->run();
            } catch (\Cycle\Database\Exception\StatementException\ConstrainException $e) {
                logs()->warning($e);

                throw new Exception('This social account is already linked to another user.');
            }
        } else {
            $userSocialNetwork = new UserSocialNetwork();
            $userSocialNetwork->value = $profile->identifier;
            $userSocialNetwork->url = $profile->profileURL;
            $userSocialNetwork->name = $profile->displayName;
            $userSocialNetwork->user = $user;
            $userSocialNetwork->socialNetwork = $socialNetworkEntity;
            $userSocialNetwork->linkedAt = new DateTimeImmutable();

            if ($social['entity']->key === 'Discord' && !empty($userSocialNetwork->value)) {
                $userSocialNetwork->url = 'https://discord.com/users/' . $userSocialNetwork->value;
            }

            $additionalData = $token ? json_decode(json_encode($token), true) : [];
            if (!empty($profile->photoURL)) {
                $additionalData['photoUrl'] = $profile->photoURL;
            }
            if (!empty($profile->data) && is_array($profile->data)) {
                $additionalData = array_merge($additionalData, $profile->data);
            }
            if ($social['entity']->key === 'Telegram') {
                $additionalData['user_id'] = $user->id;
            }
            if (!empty($additionalData)) {
                $userSocialNetwork->additional = json_encode($additionalData);
            }

            try {
                transaction($userSocialNetwork)->run();
            } catch (\Cycle\Database\Exception\StatementException\ConstrainException $e) {
                logs()->warning($e);

                throw new Exception('This social account is already linked to another user.');
            }
        }

        if (!isset($user->roles)) {
            $user = User::query()
                ->load(['roles'])
                ->where(['id' => $user->id])
                ->fetchOne();
        }

        if ($social['entity']->key === 'Discord') {
            app()->get(DiscordService::class)->linkRoles($user, $user->roles);
        }

        $this->updateAvatarFromProfileIfNeeded($user, $profile);

        $authData['adapter']->disconnect();

        try {
            $authData['adapter']->getStorage()->clear();
        } catch (Throwable $e) {
            logs()->warning($e);
        }
    }

    public function clearAuthData(): void
    {
        try {
            if ($this->hybridauth) {
                foreach ($this->hybridauth->getConnectedAdapters() as $adapter) {
                    $adapter->disconnect();

                    try {
                        $adapter->getStorage()->clear();
                    } catch (Throwable $e) {
                        logs()->warning($e);
                    }
                }
            }
        } catch (\Hybridauth\Exception\InvalidArgumentException $e) {
            logs()->warning($e);
        }
    }
}
