<?php

namespace Flute\Core\Services\Concerns\UserService;

use Flute\Core\Database\Entities\User;
use Flute\Core\Services\UserPermission;
use Throwable;

trait HandlesUserPermissions
{
    public function can(UserPermission|string|array|User $permissions, ?callable $callback = null): bool
    {
        try {
            $permissions = is_array($permissions) ? $permissions : [$permissions];

            foreach ($permissions as $permission) {
                $permissionName = $permission instanceof UserPermission ? $permission->value : $permission;

                if ($permissionName instanceof User) {
                    if (!$this->canEditUser($permissionName)) {
                        return false;
                    }
                } else {
                    if (!$this->checkUserPermission($permissionName)) {
                        return false;
                    }
                }
            }

            if ($callback) {
                try {
                    $callback();
                } catch (Throwable $callbackError) {
                    logs()->debug('Permission callback failed: ' . $callbackError->getMessage());
                }
            }

            return true;
        } catch (Throwable $e) {
            $this->handleAuthStateFailure('Permission check failed', $e);

            return false;
        }
    }

    public function getHighestPriority(?User $user = null): int
    {
        try {
            $userToCheck = $user ?? $this->currentUser;

            if (!$userToCheck) {
                return 0;
            }

            if ($userToCheck === $this->currentUser && $this->highestPriority !== null) {
                return $this->highestPriority;
            }

            $highestPriority = 0;

            foreach ($userToCheck->roles as $role) {
                if ($role->priority > $highestPriority) {
                    $highestPriority = $role->priority;
                }
            }

            if ($userToCheck === $this->currentUser) {
                $this->highestPriority = $highestPriority;
            }

            return $highestPriority;
        } catch (Throwable $e) {
            $this->handleAuthStateFailure('Highest role priority lookup failed', $e);

            return 0;
        }
    }

    public function canEditUser(User $userToEdit): bool
    {
        $currentUserHighestPriority = $this->getHighestPriority();
        $userToEditHighestPriority = $this->getHighestPriority($userToEdit);

        return $currentUserHighestPriority >= $userToEditHighestPriority
        || $this->can(UserPermission::ADMIN_BOSS->value);
    }

    /**
     * @deprecated Use `can` instead
     */
    public function hasPermission(string $permission): bool
    {
        return $this->can($permission);
    }

    public function hasRole(string $role): bool
    {
        try {
            if (!$this->isLoggedIn()) {
                return false;
            }

            if ($this->rolesCache !== null) {
                return isset($this->rolesCache[$role]);
            }

            $this->rolesCache = [];

            foreach ($this->currentUser->roles as $userRole) {
                $this->rolesCache[$userRole->name] = true;
            }

            return isset($this->rolesCache[$role]);
        } catch (Throwable $e) {
            $this->handleAuthStateFailure('Role check failed', $e);

            return false;
        }
    }

    public function hasSocialNetwork(string $key): bool
    {
        try {
            if (!$this->isLoggedIn()) {
                return false;
            }

            return !empty($this->currentUser->getSocialNetwork($key));
        } catch (Throwable $e) {
            $this->handleAuthStateFailure('Social network check failed', $e);

            return false;
        }
    }

    protected function getPermissions(): array
    {
        if (!$this->currentUser) {
            return [];
        }

        if ($this->permissionsCache !== null) {
            return $this->permissionsCache;
        }

        $this->permissionsCache = [];

        if (!isset($this->currentUser->roles[0]->permissions)) {
            $this->currentUser = $this->get($this->currentUser->id, true, ['roles', 'roles.permissions']);
        }

        foreach ($this->currentUser->getPermissions() as $permission) {
            $this->permissionsCache[$permission->name] = true;
        }

        return $this->permissionsCache;
    }

    protected function checkUserPermission(string $permission)
    {
        try {
            if (!$this->isLoggedIn()) {
                return false;
            }

            $permissions = $this->getPermissions();

            return isset($permissions[UserPermission::ADMIN_BOSS->value]) || isset($permissions[$permission]);
        } catch (Throwable $e) {
            if (!$this->permissionFailureLogged) {
                logs('database')->warning('Permission check failed: ' . $e->getMessage(), [
                    'permission' => $permission,
                ]);

                $this->permissionFailureLogged = true;
            }

            return false;
        }
    }

    protected function handleAuthStateFailure(string $message, Throwable $e): void
    {
        $this->currentUser = null;
        $this->permissionsCache = null;
        $this->rolesCache = null;
        $this->highestPriority = null;
        $this->triedToLogin = true;

        if ($this->authFailureLogged) {
            return;
        }

        logs('database')->warning($message . ': ' . $e->getMessage());
        $this->authFailureLogged = true;
    }
}
