<?php

namespace Flute\Admin\Packages\User\Services;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserBlock;
use Flute\Core\Database\Entities\UserDevice;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\Services\DiscordService;
use Flute\Core\Support\FileUploader;
use Throwable;

class AdminUsersService
{
    public function getRoles(): array
    {
        return Role::findAll();
    }

    public function getUserById(int $id): ?User
    {
        return User::findByPK($id);
    }

    public function getAllUsers(): array
    {
        return User::findAll();
    }

    /**
     * Сохранение пользователя.
     */
    public function saveUser(
        User $user,
        array $data,
        \Symfony\Component\HttpFoundation\FileBag $files,
        bool $removeAvatar = false,
        bool $removeBanner = false,
    ): void {
        $this->handleRoles($user, $data['roles'] ?? []);

        $avatarUploaded = $this->handleFile($user, $files, 'avatar', $user->avatar);
        $bannerUploaded = $this->handleFile($user, $files, 'banner', $user->banner);

        if ($removeAvatar && !$avatarUploaded) {
            $user->avatar = config('profile.default_avatar');
        }

        if ($removeBanner && !$bannerUploaded) {
            $user->banner = null;
        }

        $this->updateUserData($user, $data);

        $user->saveOrFail();
    }

    /**
     * Блокировка пользователя.
     */
    public function blockUser(User $user, array $data): void
    {
        $block = new UserBlock();
        $block->user = $user;
        $block->blockedBy = user()->getCurrentUser();
        $block->reason = $data['reason'];
        $block->blockedFrom = new DateTimeImmutable();
        $block->blockedUntil = $data['blockedUntil'] ? $this->parseDateTime($data['blockedUntil']) : null;
        $block->save();
    }

    /**
     * Разблокировка пользователя.
     */
    public function unblockUser(User $user): void
    {
        foreach ($user->blocksReceived as $block) {
            if (
                $block->isActive
                && ( $block->blockedUntil > new DateTimeImmutable() || $block->blockedUntil === null )
            ) {
                $block->isActive = false;
                $block->save();
            }
        }
    }

    /**
     * Сброс пароля пользователя.
     */
    public function resetPassword(User $user, string $password): void
    {
        $user->setPassword($password);
        $user->save();

        $this->clearUserSessions($user);
    }

    /**
     * Очистка сессий пользователя.
     */
    public function clearUserSessions(User $user): void
    {
        foreach ($user->rememberTokens as $token) {
            $token->delete();
        }

        foreach ($user->userDevices as $device) {
            $device->delete();
        }
    }

    /**
     * Завершение конкретной сессии.
     */
    public function terminateSession(User $user, int $deviceId): void
    {
        $device = UserDevice::findByPK($deviceId);

        if (!$device || $device->user->id !== $user->id) {
            throw new Exception(__('admin-users.messages.session_not_found'));
        }

        foreach ($device->rememberTokens as $token) {
            $token->delete();
        }

        $device->delete();
    }

    /**
     * Добавление социальной сети.
     */
    public function addSocialNetwork(User $user, array $data): void
    {
        $socialNetwork = SocialNetwork::findByPK($data['socialNetwork']);
        if (!$socialNetwork) {
            throw new Exception(__('admin-users.messages.social_not_found'));
        }

        $userSocialNetwork = new UserSocialNetwork();
        $userSocialNetwork->user = $user;
        $userSocialNetwork->socialNetwork = $socialNetwork;
        $userSocialNetwork->value = $data['value'];
        $userSocialNetwork->url = $data['url'];
        $userSocialNetwork->name = $data['name'];
        $userSocialNetwork->save();
    }

    /**
     * Обновление социальной сети.
     */
    public function updateSocialNetwork(int $networkId, array $data): void
    {
        $network = UserSocialNetwork::findByPK($networkId);
        if (!$network) {
            throw new Exception(__('admin-users.messages.social_not_found'));
        }

        $network->value = $data['value'];
        $network->url = $data['url'];
        $network->name = $data['name'];
        $network->save();
    }

    /**
     * Переключение видимости социальной сети.
     */
    public function toggleSocialNetworkVisibility(int $networkId): void
    {
        $network = UserSocialNetwork::findByPK($networkId);
        if (!$network) {
            throw new Exception(__('admin-users.messages.social_not_found'));
        }

        $network->hidden = !$network->hidden;
        $network->save();
    }

    /**
     * Удаление социальной сети.
     */
    public function deleteSocialNetwork(int $networkId): void
    {
        $network = rep(UserSocialNetwork::class)->findByPK($networkId);
        if (!$network) {
            throw new Exception(__('admin-users.messages.social_not_found'));
        }

        $network->delete();
    }

    /**
     * Parse datetime string from datetime-local input.
     * The input comes in format Y-m-d\TH:i without timezone info.
     * We interpret it as the application's configured timezone.
     */
    private function parseDateTime(string $dateTimeString): DateTimeImmutable
    {
        $timezone = new DateTimeZone(config('app.timezone') ?: date_default_timezone_get());

        return new DateTimeImmutable($dateTimeString, $timezone);
    }

    /**
     * Обработка ролей пользователя.
     */
    private function handleRoles(User $user, array $roleIds): void
    {
        $userHighestPriority = user()->getHighestPriority();
        $currentRoles = $user->roles;

        $hasBossAccess = user()->can('admin.boss');

        $user->clearRoles();

        if ($hasBossAccess) {
            $selectedRoles = array_filter(Role::findAll(), static fn($role) => in_array(
                $role->id,
                array_map('intval', $roleIds),
                true,
            ));

            foreach ($selectedRoles as $role) {
                $user->addRole($role);
            }
        } else {
            foreach ($currentRoles as $role) {
                if ($role->priority >= $userHighestPriority) {
                    $user->addRole($role);
                }
            }

            if (!empty($roleIds)) {
                $allowedRoles = array_filter(
                    Role::findAll(),
                    static fn($role) => (
                        in_array($role->id, array_map('intval', $roleIds), true)
                        && $role->priority < $userHighestPriority
                    ),
                );

                foreach ($allowedRoles as $role) {
                    $user->addRole($role);
                }
            }
        }

        $user->saveOrFail();

        try {
            if ($user->getSocialNetwork('Discord')) {
                app()->get(DiscordService::class)->linkRoles($user, $user->roles);
            }
        } catch (Throwable $e) {
            logs()->warning($e);
        }
    }

    /**
     * Process a single file upload field (avatar or banner).
     * Returns true if a new file was actually uploaded.
     */
    private function handleFile(
        User $user,
        \Symfony\Component\HttpFoundation\FileBag $files,
        string $field,
        ?string $currentPath,
    ): bool {
        if (!$files->has($field)) {
            return false;
        }

        $file = $files->get($field);

        if (!$this->isValidNewUpload($file, $currentPath)) {
            return false;
        }

        /** @var FileUploader $uploader */
        $uploader = app(FileUploader::class);

        try {
            $uploaded = $uploader->uploadImage($file, 10);
            if ($uploaded) {
                $user->{$field} = $uploaded;

                return true;
            }
        } catch (Throwable $e) {
            throw new Exception(__("admin-users.messages.{$field}_upload_error", ['message' => $e->getMessage()]));
        }

        return false;
    }

    /**
     * Checks whether the file is a genuinely new upload (not a re-fetched default).
     *
     * FilePond with storeAsFile re-sends existing images as blobs fetched from
     * their current URL. We detect these by comparing the uploaded filename
     * against the basename of the currently stored path.
     */
    private function isValidNewUpload($file, ?string $currentPath = null): bool
    {
        if (!$file || !$file->isValid()) {
            return false;
        }

        if ($file->getSize() === 0) {
            return false;
        }

        $originalName = $file->getClientOriginalName();

        if (
            $originalName
            && ( str_starts_with($originalName, 'http://') || str_starts_with($originalName, 'https://') )
        ) {
            return false;
        }

        if ($originalName && ( str_starts_with($originalName, '/') || str_starts_with($originalName, 'assets/') )) {
            return false;
        }

        $tempPath = $file->getPathname();
        if ($tempPath) {
            $firstBytes = @file_get_contents($tempPath, false, null, 0, 256);
            if ($firstBytes !== false) {
                $trimmed = trim($firstBytes);
                if (
                    str_starts_with($trimmed, 'http://')
                    || str_starts_with($trimmed, 'https://')
                    || str_starts_with($trimmed, '/')
                ) {
                    return false;
                }
            }
        }

        if ($currentPath && $originalName) {
            $currentBasename = basename($currentPath);
            $nameWithoutQuery = explode('?', $originalName)[0];
            if ($nameWithoutQuery === $currentBasename) {
                return false;
            }
        }

        return true;
    }

    /**
     * Обновление данных пользователя.
     */
    private function updateUserData(User $user, array $data): void
    {
        $user->name = $data['name'];
        if (!empty($data['login'])) {
            $user->login = $data['login'];
        }
        if (!empty($data['uri'])) {
            $user->uri = $data['uri'];
        }
        if (!empty($data['email']) && $data['email'] !== $user->email) {
            if (config('auth.registration.confirm_email')) {
                $user->pendingEmail = $data['email'];

                try {
                    template()->addNamespace('flute', path('app/Themes/standard/views'));
                    $verificationToken = auth()->createVerificationToken($user)->rawToken;
                    $html = template()->render('flute::emails.confirmation', [
                        'url' => url('confirm-email/' . $verificationToken),
                        'name' => $user->name,
                    ]);
                    email()->send($data['email'], __('auth.confirmation.subject'), $html);
                } catch (\Throwable $e) {
                    logs()->warning('Failed to send email change confirmation from admin: ' . $e->getMessage());
                }
            } else {
                $user->email = $data['email'];
            }
        }
        $user->balance = floatval($data['balance']);
        $user->verified = isset($data['verified']) ? filter_var($data['verified'], FILTER_VALIDATE_BOOLEAN) : false;
        $user->hidden = isset($data['hidden']) ? filter_var($data['hidden'], FILTER_VALIDATE_BOOLEAN) : false;

        if (isset($data['approved']) && user()->can('admin.boss')) {
            $user->approved = filter_var($data['approved'], FILTER_VALIDATE_BOOLEAN);
        }

        $user->save();
    }
}
