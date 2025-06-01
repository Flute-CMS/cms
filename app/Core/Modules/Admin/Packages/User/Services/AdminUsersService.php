<?php

namespace Flute\Admin\Packages\User\Services;

use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserBlock;
use Flute\Core\Database\Entities\UserDevice;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Support\FileUploader;

class AdminUsersService
{
    public function getRoles() : array
    {
        return Role::findAll();
    }

    public function getUserById(int $id) : ?User
    {
        return User::findByPK($id);
    }

    public function getAllUsers() : array
    {
        return User::findAll();
    }

    /**
     * Сохранение пользователя.
     */
    public function saveUser(User $user, array $data, \Symfony\Component\HttpFoundation\FileBag $files) : void
    {
        if (isset($data['roles']) && sizeof($data['roles']) !== sizeof($user->roles)) {
            $this->handleRoles($user, $data['roles']);
        }

        $this->handleFiles($user, $files);
        $this->updateUserData($user, $data);

        $user->save();
    }

    /**
     * Обработка ролей пользователя.
     */
    private function handleRoles(User $user, array $roleIds) : void
    {
        $userHighestPriority = user()->getHighestPriority();
        $currentRoles = $user->roles;

        $hasBossAccess = user()->can('admin.boss');
        
        if ($hasBossAccess) {
            $untouchableRoles = [];
            $selectedRoles = array_filter(
                Role::findAll(),
                fn ($role) => in_array($role->id, $roleIds)
            );
        } else {
            $untouchableRoles = array_filter($currentRoles, fn ($role) => $role->priority >= $userHighestPriority);
            $selectedRoles = array_filter(
                Role::findAll(),
                fn ($role) => in_array($role->id, $roleIds) && $role->priority < $userHighestPriority
            );
        }

        $user->clearRoles();

        foreach ($untouchableRoles as $role) {
            $user->addRole($role);
        }

        foreach ($selectedRoles as $role) {
            $user->addRole($role);
        }

        $user->saveOrFail();
    }

    /**
     * Обработка загруженных файлов.
     */
    private function handleFiles(User $user, \Symfony\Component\HttpFoundation\FileBag $files) : void
    {
        /** @var FileUploader $uploader */
        $uploader = app(FileUploader::class);

        if ($files->has('avatar') && $files->get('avatar')->isValid()) {
            try {
                $avatar = $uploader->uploadImage($files->get('avatar'), 10);
                if ($avatar) {
                    $user->avatar = $avatar;
                }
            } catch (\Exception $e) {
                throw new \Exception(__('admin-users.messages.avatar_upload_error', ['message' => $e->getMessage()]));
            }
        }

        if ($files->has('banner') && $files->get('banner')->isValid()) {
            try {
                $banner = $uploader->uploadImage($files->get('banner'), 10);
                if ($banner) {
                    $user->banner = $banner;
                }
            } catch (\Exception $e) {
                throw new \Exception(__('admin-users.messages.banner_upload_error', ['message' => $e->getMessage()]));
            }
        }
    }

    /**
     * Обновление данных пользователя.
     */
    private function updateUserData(User $user, array $data) : void
    {
        $user->name = $data['name'];
        $user->login = $data['login'];
        $user->uri = $data['uri'];
        $user->email = $data['email'];
        $user->balance = (float) $data['balance'];
        $user->verified = isset($data['verified']) ? filter_var($data['verified'], FILTER_VALIDATE_BOOLEAN) : false;
        $user->hidden = isset($data['hidden']) ? filter_var($data['hidden'], FILTER_VALIDATE_BOOLEAN) : false;

        $user->save();
    }

    /**
     * Блокировка пользователя.
     */
    public function blockUser(User $user, array $data) : void
    {
        $block = new UserBlock();
        $block->user = $user;
        $block->blockedBy = user()->getCurrentUser();
        $block->reason = $data['reason'];
        $block->blockedFrom = new \DateTimeImmutable();
        $block->blockedUntil = $data['blockedUntil'] ? new \DateTimeImmutable($data['blockedUntil']) : null;
        $block->save();
    }

    /**
     * Разблокировка пользователя.
     */
    public function unblockUser(User $user) : void
    {
        foreach ($user->blocksReceived as $block) {
            if ($block->isActive && ($block->blockedUntil > new \DateTimeImmutable() || $block->blockedUntil === null)) {
                $block->isActive = false;
                $block->save();
            }
        }
    }

    /**
     * Сброс пароля пользователя.
     */
    public function resetPassword(User $user, string $password) : void
    {
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->save();

        $this->clearUserSessions($user);
    }

    /**
     * Очистка сессий пользователя.
     */
    public function clearUserSessions(User $user) : void
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
    public function terminateSession(User $user, int $deviceId) : void
    {
        $device = UserDevice::findByPK($deviceId);

        if (! $device || $device->user->id !== $user->id) {
            throw new \Exception(__('admin-users.messages.session_not_found'));
        }

        foreach ($device->rememberTokens as $token) {
            $token->delete();
        }

        $device->delete();
    }

    /**
     * Добавление социальной сети.
     */
    public function addSocialNetwork(User $user, array $data) : void
    {
        $socialNetwork = SocialNetwork::findByPK($data['socialNetwork']);
        if (! $socialNetwork) {
            throw new \Exception(__('admin-users.messages.social_not_found'));
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
    public function updateSocialNetwork(int $networkId, array $data) : void
    {
        $network = UserSocialNetwork::findByPK($networkId);
        if (! $network) {
            throw new \Exception(__('admin-users.messages.social_not_found'));
        }

        $network->value = $data['value'];
        $network->url = $data['url'];
        $network->name = $data['name'];
        $network->save();
    }

    /**
     * Переключение видимости социальной сети.
     */
    public function toggleSocialNetworkVisibility(int $networkId) : void
    {
        $network = UserSocialNetwork::findByPK($networkId);
        if (! $network) {
            throw new \Exception(__('admin-users.messages.social_not_found'));
        }

        $network->hidden = ! $network->hidden;
        $network->save();
    }

    /**
     * Удаление социальной сети.
     */
    public function deleteSocialNetwork(int $networkId) : void
    {
        $network = rep(UserSocialNetwork::class)->findByPK($networkId);
        if (! $network) {
            throw new \Exception(__('admin-users.messages.social_not_found'));
        }

        $network->delete();
    }
}
