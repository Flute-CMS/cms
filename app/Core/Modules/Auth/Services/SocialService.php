<?php

namespace Flute\Core\Modules\Auth\Services;

use DateTimeImmutable;
use Exception;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\Exceptions\NeedRegistrationException;
use Flute\Core\Exceptions\SocialNotFoundException;
use Flute\Core\Modules\Auth\Contracts\SocialServiceInterface;
use Flute\Core\Modules\Auth\Events\SocialProviderAddedEvent;
use Flute\Core\Modules\Auth\Events\UserRegisteredEvent;
use Flute\Core\Modules\Auth\Hybrid\Storage\StorageSession;
use Flute\Core\Services\DiscordService;
use Hybridauth\Hybridauth;
use Hybridauth\User\Profile;
use Throwable;

class SocialService implements SocialServiceInterface
{
    /** @var Hybridauth */
    private $hybridauth;

    /**  */
    private array $registeredProviders = [];

    /**
     * Settings that should be stored on provider root level, not inside "keys".
     */
    private array $nonKeySettingFields = ['scope', 'fields', 'display', 'version', 'service_token'];

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->initializeProviders();
        $this->overrideDefaultProviders();
    }

    // ===== Registering Providers =====

    /**
     * Registers a new social network.
     *
     * @param array $config Configuration settings for the social network.
     */
    public function registerSocialNetwork(array $config): void
    {
        $socialNetwork = new SocialNetwork();
        $socialNetwork->key = $config['key'];
        $socialNetwork->settings = json_encode($this->prepareSettingsPayload($config['key'], $config['settings'] ?? []));
        $socialNetwork->icon = $config['icon'] ?? '';
        $socialNetwork->enabled = $config['enabled'] ?? true;
        $socialNetwork->allowToRegister = $config['allowToRegister'] ?? true;
        $socialNetwork->cooldownTime = $config['cooldownTime'] ?? 0;

        transaction($socialNetwork)->run();

        $this->registerSocial($socialNetwork);
    }

    /**
     * Registers all active social networks.
     */
    public function registerSocials()
    {
        $providers = SocialNetwork::findAll(['enabled' => true]);

        foreach ($providers as $socialNetwork) {
            $this->registerSocial($socialNetwork);
        }
    }

    /**
     * Registers a single social network.
     *
     * @param SocialNetwork $socialNetwork The social network entity to register.
     */
    public function registerSocial(SocialNetwork $socialNetwork)
    {
        $providerName = $this->normalizeProviderName($socialNetwork->key);
        $settings = json_decode($socialNetwork->settings, true) ?? [];

        $settings = $this->normalizeSettingsStructure($socialNetwork->key, $settings);
        $settings = $this->mapProviderSettings($socialNetwork->key, $settings);

        $this->registeredProviders[$providerName] = array_merge([
            'enabled' => true,
            'entity' => $socialNetwork,
        ], $settings);

        events()->dispatch(new SocialProviderAddedEvent($socialNetwork), SocialProviderAddedEvent::NAME);
    }

    /**
     * Adds a new social network.
     *
     * @param string $key Unique key identifier for the social network.
     * @param array $settings Configuration settings for the social network.
     * @param string $icon Icon URL or path for the social network.
     * @param bool $enabled Whether the social network is enabled.
     * @return SocialNetwork The newly added social network entity.
     */
    public function addSocial(string $key, array $settings, string $icon, bool $enabled = true): SocialNetwork
    {
        $socialNetwork = new SocialNetwork();
        $socialNetwork->key = $key;
        $socialNetwork->settings = json_encode($settings);
        $socialNetwork->icon = $icon;
        $socialNetwork->enabled = $enabled;

        transaction($socialNetwork)->run();

        $this->registerSocial($socialNetwork);

        return $socialNetwork;
    }

    // ===== Getting Providers =====

    /**
     * Retrieves all registered providers.
     *
     * @param bool $onlyAllowed Whether to retrieve only providers allowed for registration.
     * @return array List of registered providers.
     */
    public function getAll(bool $onlyAllowed = true): array
    {
        return $onlyAllowed ? $this->getAllowedProviders() : $this->registeredProviders;
    }

    /**
     * Retrieves all providers (alias for getAll).
     *
     * @param bool $onlyAllowed Whether to retrieve only providers allowed for registration.
     * @return array List of providers.
     */
    public function getAllProviders(bool $onlyAllowed = true): array
    {
        return $this->getAll($onlyAllowed);
    }

    /**
     * Checks if there are any registered providers.
     *
     * @return bool True if no providers are registered, false otherwise.
     */
    public function isEmpty(): bool
    {
        return empty($this->registeredProviders);
    }

    /**
     * Converts providers for display purposes.
     *
     * @return array List of providers formatted for display.
     */
    public function toDisplay(): array
    {
        $result = [];

        foreach ($this->getAll() as $provider) {
            $key = $provider['entity']->key;
            if ($key === 'HttpsSteam') {
                $result['Steam'] = $provider['entity']->icon;
            } else {
                $result[$key] = $provider['entity']->icon;
            }
        }

        return $result;
    }

    /**
     * Retrieves a provider by name.
     *
     * @param string $socialNetworkName The name of the social network.
     * @throws SocialNotFoundException If the provider is not found.
     * @return array The provider's configuration.
     */
    public function retrieveSocialNetwork(string $socialNetworkName): array
    {
        if (isset($this->registeredProviders[$socialNetworkName])) {
            return $this->registeredProviders[$socialNetworkName];
        }

        foreach ($this->registeredProviders as $key => $provider) {
            if (strcasecmp($key, $socialNetworkName) === 0) {
                return $provider;
            }
        }

        throw new SocialNotFoundException($socialNetworkName);
    }

    // ===== Authentication =====

    /**
     * Authenticates a user via a social network with possible registration.
     *
     * @param string $providerName The name of the social provider.
     * @throws Exception If registration is not allowed or other errors occur.
     * @return User The authenticated user.
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
            throw new NeedRegistrationException($authData['profile']);
        }

        return $this->registerNewUser($authData['profile'], $social['entity']);
    }

    /**
     * Authenticates a user via a social network.
     *
     * @param string $providerName The name of the social provider.
     * @param bool $bind Whether to bind the social account to an existing user.
     * @throws Exception If user profile cannot be loaded.
     * @return array Authentication data including user profile and adapter.
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

    /**
     * Registers a new user via a social network.
     *
     * @param Profile $userProfile The user's social profile.
     * @param SocialNetwork $socialNetwork The social network entity.
     * @return User The newly registered user.
     */
    public function registerNewUser(Profile $userProfile, SocialNetwork $socialNetwork): User
    {
        $email = $userProfile->email;

        if ($email) {
            $existingUser = User::query()
                ->where(['email' => $email])
                ->fetchOne();

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

        events()->dispatch(new UserRegisteredEvent($user), UserRegisteredEvent::NAME);

        if ($socialNetwork->key === "Discord") {
            app()->get(DiscordService::class)->linkRoles($user, $user->roles);
        }

        $this->updateBannerFromProfileIfNeeded($user, $userProfile);

        return $user;
    }

    /**
     * Binds a social network to an existing user.
     *
     * @param User $user The user to bind the social network to.
     * @param string $socialNetworkName The name of the social network.
     * @throws Exception If binding fails due to cooldown or other issues.
     */
    public function bindSocialNetwork(User $user, string $socialNetworkName): void
    {
        $normalized = $this->normalizeProviderName($socialNetworkName);
        $authData = $this->authenticate($normalized, true);
        $social = $this->retrieveSocialNetwork($normalized);

        $userSocialNetwork = UserSocialNetwork::query()
            ->where([
                'user.id' => $user->id,
                'socialNetwork.id' => $social['entity']->id,
                'user.isTemporary' => false,
            ])
            ->fetchOne();

        $profile = $authData['profile'];
        $token = $authData['adapter']->getAccessToken();

        $existingSocial = UserSocialNetwork::query()
            ->where(['value' => $profile->identifier])
            ->load(['user'])
            ->fetchOne();

        if ($existingSocial && $existingSocial->user->id !== $user->id) {
            if ($existingSocial->user->isTemporary()) {
                $tempUserId = $existingSocial->user->id;

                try {
                    transaction($existingSocial, 'delete')->run();
                    $tempUser = User::findByPK($tempUserId);
                    if ($tempUser) {
                        transaction($tempUser, 'delete')->run();
                    }
                } catch (Exception $e) {
                    logs()->error("Error deleting temporary user during bind: " . $e->getMessage());

                    throw new Exception('Failed to reassign social account. Please try again.');
                }
            } else {
                throw new Exception('This social account is already linked to another user.');
            }
        }
        $now = new DateTimeImmutable();

        if ($userSocialNetwork) {
            $lastLinked = $userSocialNetwork->linkedAt;

            if ($social['entity']->cooldownTime > 0 && $lastLinked && ($now->getTimestamp() - $lastLinked->getTimestamp() < $social['entity']->cooldownTime)) {
                throw new Exception(__('profile.errors.social_delay'));
            }

            $userSocialNetwork->value = $profile->identifier;
            $userSocialNetwork->url = $profile->profileURL;
            $userSocialNetwork->name = $profile->displayName;
            $userSocialNetwork->linkedAt = new DateTimeImmutable();

            if ($token) {
                $userSocialNetwork->additional = json_encode($token);
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
            $userSocialNetwork->socialNetwork = $social['entity'];
            $userSocialNetwork->linkedAt = new DateTimeImmutable();

            if ($token) {
                $userSocialNetwork->additional = json_encode($token);
            }

            try {
                transaction($userSocialNetwork)->run();
            } catch (\Cycle\Database\Exception\StatementException\ConstrainException $e) {
                logs()->warning($e);

                throw new Exception('This social account is already linked to another user.');
            }
        }

        if (!isset($user->roles)) {
            $user = User::query()->load(['roles'])->where(['id' => $user->id])->fetchOne();
        }

        if ($social['entity']->key === "Discord") {
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

    /**
     * Clears authentication data.
     */
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

    /**
     * Retrieves allowed providers for registration.
     *
     * @return array List of allowed providers.
     */
    protected function getAllowedProviders(): array
    {
        $result = [];

        foreach ($this->registeredProviders as $key => $provider) {
            if ($provider['entity']->allowToRegister === true) {
                $result[$key] = $provider;
            }
        }

        return $result;
    }

    /**
     * Replaces the name of the social network.
     *
     * @param string $socialName The original social network name.
     * @return string The replaced social network name.
     */
    protected function replaceName(string $socialName)
    {
        return $this->normalizeProviderName($socialName);
    }

    // ===== Initialization =====

    /**
     * Initializes registered social providers.
     */
    private function initializeProviders(): void
    {
        $providers = SocialNetwork::findAll(['enabled' => true]);

        foreach ($providers as $socialNetwork) {
            $this->registerSocial($socialNetwork);
        }
    }

    /**
     * Overrides default social providers.
     */
    private function overrideDefaultProviders(): void
    {
        $this->replaceDiscordProvider();
        $this->initializePSR();
    }

    /**
     * Initializes PSR-4 autoloading for custom providers.
     */
    private function initializePSR()
    {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, 'Hybridauth\\Provider\\');
        app()->getLoader()->addPsr4('Hybridauth\\Provider\\', BASE_PATH . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'Hybrid');
    }

    /**
     * Replaces the standard Discord provider with a custom one.
     */
    private function replaceDiscordProvider()
    {
        $loader = app()->getLoader();

        $path = str_replace('\\', DIRECTORY_SEPARATOR, 'Hybridauth\\Provider\\Discord');

        $loader->addClassMap([
            $path => BASE_PATH . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'Hybrid' . DIRECTORY_SEPARATOR . 'Discord.php',
        ]);

        $loader->register();
    }

    /**
     * Initializes Hybridauth with the given provider.
     *
     * @param string|null $providerName The name of the social provider.
     * @param bool $bind Whether to bind the social account to an existing user.
     */
    private function initializeHybridAuth(?string $providerName = null, bool $bind = false): void
    {
        $callbackPath = $bind ? "profile/social/bind/{$providerName}" : "social/{$providerName}";
        $callbackUrl = url($callbackPath)->get();

        $this->hybridauth = new Hybridauth([
            'callback' => $callbackUrl,
            'providers' => $this->registeredProviders,
        ], null, new StorageSession());
    }

    /**
     * Finds a user by their social profile.
     *
     * @param Profile $profile The user's social profile.
     * @return User|null The user if found, null otherwise.
     */
    private function findUserBySocialProfile(Profile $profile): ?User
    {
        $userSocial = UserSocialNetwork::query()
            ->load(['user', 'user.roles'])
            ->where('user.isTemporary', false)
            ->where(['value' => $profile->identifier])
            ->fetchOne();

        return $userSocial ? $userSocial->user : null;
    }

    /**
     * Finds and deletes a temporary user by social network key and identifier.
     *
     * @param string $key The social network key.
     * @param string $identifier The user identifier.
     */
    private function findAndDeleteTemporaryUser(string $key, string $identifier): void
    {
        try {
            $userSocialNetwork = UserSocialNetwork::query()
                ->where(['socialNetwork.key' => $key, 'value' => $identifier, 'user.isTemporary' => true])
                ->load(['user'])
                ->fetchOne();

            if ($userSocialNetwork) {
                $userId = $userSocialNetwork->user->id;

                transaction($userSocialNetwork, 'delete')->run();

                $user = User::findByPK($userId);
                if ($user) {
                    transaction($user, 'delete')->run();
                }
            }
        } catch (Exception $e) {
            logs()->error("Error deleting temporary user: " . $e->getMessage());
        }
    }

    // ===== Utilities =====

    /**
     * Maps provider settings for specific providers that need special handling.
     *
     * @param string $providerKey The provider key.
     * @param array $settings The original settings.
     * @return array The mapped settings.
     */
    private function mapProviderSettings(string $providerKey, array $settings): array
    {
        $settings['keys'] = $this->mapProviderKeys($providerKey, $settings['keys'] ?? []);

        if ($providerKey === 'Vkontakte') {
            $settings['scope'] ??= 'email';
            $settings['fields'] ??= 'photo_max,screen_name';
            $settings['version'] ??= '5.131';
        }

        return $settings;
    }

    /**
     * Normalizes the provider name.
     *
     * @param string $providerName The original provider name.
     * @return string The normalized provider name.
     */
    private function normalizeProviderName(string $providerName): string
    {
        $lower = strtolower($providerName);
        if ($lower === 'steam') {
            return 'HttpsSteam';
        }

        if (isset($this->registeredProviders[$providerName])) {
            return $providerName;
        }

        foreach (array_keys($this->registeredProviders) as $registeredKey) {
            if (strcasecmp($registeredKey, $providerName) === 0) {
                return $registeredKey;
            }
        }

        return $providerName;
    }

    /**
     * Prepare settings to be stored in database.
     */
    private function prepareSettingsPayload(string $providerKey, array $settings): array
    {
        return $this->normalizeSettingsStructure($providerKey, $settings);
    }

    /**
     * Ensure provider settings have correct shape (keys + top-level options).
     */
    private function normalizeSettingsStructure(string $providerKey, array $settings): array
    {
        $keys = $settings['keys'] ?? [];

        foreach ($this->nonKeySettingFields as $field) {
            if (isset($keys[$field]) && !isset($settings[$field])) {
                $settings[$field] = $keys[$field];
                unset($keys[$field]);
            }
        }

        foreach (['id', 'key', 'client_id', 'clientId'] as $idField) {
            if (isset($settings[$idField]) && !isset($keys['id'])) {
                $keys['id'] = $settings[$idField];
            }
        }

        foreach (['secret', 'client_secret', 'clientSecret'] as $secretField) {
            if (isset($settings[$secretField]) && !isset($keys['secret'])) {
                $keys['secret'] = $settings[$secretField];
            }
        }

        $settings['keys'] = $keys;

        return $settings;
    }

    /**
     * Normalize provider keys where Hybridauth expects different names.
     */
    private function mapProviderKeys(string $providerKey, array $keys): array
    {
        if ($providerKey === 'Twitter' && isset($keys['id'])) {
            $keys['key'] = $keys['id'];
        }

        if (!isset($keys['id']) && isset($keys['key'])) {
            $keys['id'] = $keys['key'];
        }

        return $keys;
    }

    /**
     * Ensures that registration via the social network is allowed.
     *
     * @param array $social The social network configuration.
     * @throws Exception If registration is not allowed.
     */
    private function ensureRegistrationAllowed(array $social): void
    {
        if (!$social['entity']->allowToRegister) {
            throw new Exception(__('def.not_found'));
        }
    }

    /**
     * Checks if additional registration steps are required.
     *
     * @return bool True if additional registration is required, false otherwise.
     */
    private function requiresAdditionalRegistration(): bool
    {
        return config('auth.registration.social_supplement');
    }

    /**
     * Update user's avatar from social profile if current avatar is default or empty.
     */
    private function updateAvatarFromProfileIfNeeded(User $user, Profile $profile): void
    {
        try {
            $currentAvatar = $user->avatar ?? '';
            $defaultAvatar = config('profile.default_avatar');

            $isDefault = empty($currentAvatar) || $currentAvatar === $defaultAvatar || str_contains($currentAvatar, basename($defaultAvatar));

            $photoUrl = $profile->photoURL ?? null;

            if ($isDefault && $photoUrl) {
                $user->avatar = $photoUrl;

                try {
                    transaction($user)->run();
                } catch (Exception $e) {
                    logs()->warning('Failed to update user avatar from social profile: ' . $e->getMessage());
                }
            }
        } catch (Throwable $e) {
            logs()->warning('Error while updating avatar from social profile: ' . $e->getMessage());
        }
    }

    /**
     * Update user's banner from social profile if current banner is default or empty.
     */
    private function updateBannerFromProfileIfNeeded(User $user, Profile $profile): void
    {
        try {
            $currentBanner = $user->banner ?? '';
            $defaultBanner = config('profile.default_banner');

            $isDefault = empty($currentBanner) || $currentBanner === $defaultBanner || str_contains($currentBanner, basename($defaultBanner));

            $bannerUrl = $profile->data['bannerURL'] ?? null;

            if ($isDefault && $bannerUrl) {
                $user->banner = $bannerUrl;

                try {
                    $user->saveOrFail();
                } catch (Exception $e) {
                    logs()->warning('Failed to update user banner from social profile: ' . $e->getMessage());
                }
            }
        } catch (Throwable $e) {
            logs()->warning('Error while updating banner from social profile: ' . $e->getMessage());
        }
    }
}
