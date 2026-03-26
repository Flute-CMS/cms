<?php

namespace Flute\Core\Modules\Auth\Components;

use DateTimeImmutable;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\Modules\Auth\Events\UserRegisteredEvent;
use Flute\Core\Services\DiscordService;
use Flute\Core\Support\FluteComponent;

class SocialSupplementComponent extends FluteComponent
{
    public ?string $login = null;

    public ?string $email = null;

    public ?string $password = null;

    public ?string $password_confirmation = null;

    public function complete()
    {
        try {
            $this->throttle('social_supplement', 5, 60, 3);
        } catch (TooManyRequestsException $e) {
            toast()->error(__('auth.too_many_requests'))->push();

            return;
        }

        if (!$this->validator()) {
            return;
        }

        $pending = $this->readPendingProfile();

        if (!$pending) {
            toast()->error(__('auth.supplement.session_expired'))->push();
            $this->redirectTo(url('/login'), 1500);

            return;
        }

        // Check login uniqueness
        $existingLogin = User::query()->where(['login' => $this->login])->fetchOne();
        if ($existingLogin) {
            toast()->error(__('auth.duplicate_login'))->push();

            return;
        }

        // Check email uniqueness if provided
        if (!empty($this->email)) {
            $existingEmail = User::query()->where(['email' => $this->email])->fetchOne();
            if ($existingEmail) {
                toast()->error(__('auth.duplicate_email'))->push();

                return;
            }
        }

        $socialId = $pending['social_id'] ?? null;
        $social = $socialId ? SocialNetwork::findByPK($socialId) : null;

        if (!$social) {
            toast()->error(__('auth.errors.social_not_found'))->push();

            return;
        }

        $this->findAndDeleteTemporaryUser($social->key, $pending['identifier']);

        $avatarPath = $pending['photoURL'] ?? config('profile.default_avatar');

        $user = new User();
        $user->name = mb_substr($pending['displayName'] ?? $this->login, 0, 255);
        $user->login = $this->login;
        $email = !empty($this->email) ? $this->email : null;

        // If user didn't provide email, try social profile email (with uniqueness check)
        if (!$email && !empty($pending['email'])) {
            $emailTaken = User::query()->where(['email' => $pending['email']])->fetchOne();
            if (!$emailTaken) {
                $email = $pending['email'];
            }
        }

        $user->email = $email;
        $user->uri = null;
        $user->avatar = $avatarPath;
        $user->verified = true;

        if (!empty($this->password)) {
            $user->setPassword($this->password);
        }

        $userSocialNetwork = new UserSocialNetwork();
        $userSocialNetwork->value = $pending['identifier'];
        $userSocialNetwork->url = $pending['profileURL'] ?? null;
        $userSocialNetwork->name = $pending['displayName'] ?? null;
        $userSocialNetwork->user = $user;
        $userSocialNetwork->socialNetwork = $social;
        $userSocialNetwork->linkedAt = new DateTimeImmutable();

        if ($social->key === 'Discord' && !empty($userSocialNetwork->value)) {
            $userSocialNetwork->url = 'https://discord.com/users/' . $userSocialNetwork->value;
        }

        if (!empty($pending['photoURL'])) {
            $userSocialNetwork->setAdditional(['photoUrl' => $pending['photoURL']]);
        }

        try {
            transaction([$user, $userSocialNetwork])->run();
        } catch (\Cycle\Database\Exception\StatementException\ConstrainException $e) {
            logs()->warning($e);
            toast()->error(__('auth.duplicate_login'))->push();

            return;
        }

        events()->dispatch(new UserRegisteredEvent($user), UserRegisteredEvent::NAME);

        if ($social->key === 'Discord') {
            app()->get(DiscordService::class)->linkRoles($user, $user->roles);
        }

        $this->clearPendingProfile();

        auth()->authenticateById($user->id, config('auth.remember_me'), true);

        toast()->success(__('auth.register_success'))->push();

        $this->redirectTo(url('/'), 1500);
    }

    public function render()
    {
        $pending = $this->readPendingProfile();

        if (!$pending) {
            $this->redirectTo(url('/login'));

            return $this->view('flute::components.auth.social-supplement', [
                'login' => $this->login,
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'emailFromSocial' => false,
            ]);
        }

        $emailFromSocial = !empty($pending['email']);

        if ($this->login === null && !empty($pending['displayName'])) {
            $this->login = $this->sanitizeLogin($pending['displayName']);
        }

        if ($this->email === null && $emailFromSocial) {
            $this->email = $pending['email'];
        }

        return $this->view('flute::components.auth.social-supplement', [
            'login' => $this->login,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'emailFromSocial' => $emailFromSocial,
        ]);
    }

    protected function readPendingProfile(): ?array
    {
        $encrypted = session()->get('social_supplement');

        if (!$encrypted) {
            return null;
        }

        try {
            $data = json_decode(encrypt()->decrypt($encrypted), true);
        } catch (\Throwable $e) {
            return null;
        }

        if (empty($data['issued_at']) || ( time() - $data['issued_at'] ) > 900) {
            $this->clearPendingProfile();

            return null;
        }

        return $data;
    }

    protected function clearPendingProfile(): void
    {
        session()->remove('social_supplement');
    }

    protected function validator(): bool
    {
        $rules = [
            'login' => [
                'required',
                'regex:/^[\p{L}\p{N}._-]+$/u',
                'min-str-len:' . config('auth.validation.login.min_length'),
                'max-str-len:' . config('auth.validation.login.max_length'),
            ],
            'email' => [
                'email',
                'max-str-len:255',
            ],
        ];

        $data = [
            'login' => $this->login,
            'email' => $this->email,
        ];

        if (!empty($this->password)) {
            $rules['password'] = [
                'confirmed',
                'min-str-len:' . config('auth.validation.password.min_length'),
                'max-str-len:' . config('auth.validation.password.max_length'),
            ];
            $data['password'] = $this->password;
            $data['password_confirmation'] = $this->password_confirmation;
        }

        return validator()->validate($data, $rules);
    }

    protected function sanitizeLogin(string $displayName): string
    {
        // Remove emojis and special symbols, keep letters, digits, dots, hyphens, underscores
        $clean = preg_replace('/[^\p{L}\p{N}._-]/u', '', $displayName);

        $minLen = (int) config('auth.validation.login.min_length', 4);
        $maxLen = (int) config('auth.validation.login.max_length', 20);

        $clean = mb_substr($clean, 0, $maxLen);

        if (mb_strlen($clean) < $minLen) {
            return '';
        }

        return $clean;
    }

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

                $tempUser = User::findByPK($userId);
                if ($tempUser) {
                    transaction($tempUser, 'delete')->run();
                }
            }
        } catch (\Exception $e) {
            logs()->error('Error deleting temporary user: ' . $e->getMessage());
        }
    }
}
