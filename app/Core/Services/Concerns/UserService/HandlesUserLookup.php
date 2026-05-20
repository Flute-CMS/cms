<?php

namespace Flute\Core\Services\Concerns\UserService;

use Flute\Core\Database\Entities\User;
use Flute\Core\Events\UserChangedEvent;
use Throwable;

trait HandlesUserLookup
{
    public function getByRoute(string $route, bool $force = false): ?User
    {
        if (isset($this->usersCache[$route]) && !$force) {
            return $this->usersCache[$route];
        }

        $user = User::query()
            ->where(['uri' => $route])
            ->load('blocksReceived')
            ->fetchOne();

        if ($user) {
            $this->usersCache[$user->id] = $user;
            $this->usersCache[$route] = $user;
        }

        return $this->usersCache[$route] ?? null;
    }

    public function get(
        int $userId,
        bool $force = false,
        array $with = ['roles', 'roles.permissions', 'blocksReceived'],
    ): ?User {
        if (isset($this->usersCache[$userId]) && !$force) {
            return $this->usersCache[$userId];
        }

        $query = User::query();

        if (!empty($with)) {
            $query->load($with);
        }

        $user = $query->where(['id' => $userId])->fetchOne();

        if ($user) {
            $this->usersCache[$userId] = $user;
        }

        return $this->usersCache[$userId] ?? null;
    }

    public function getCurrentUser(): ?User
    {
        try {
            if (!$this->currentUser) {
                $this->authSession();
            }
        } catch (Throwable $e) {
            $this->handleAuthStateFailure('Current user restore failed', $e);

            return null;
        }

        return $this->currentUser;
    }

    public function setCurrentUser(User $user): self
    {
        $this->currentUser = $user;

        events()->dispatch(new UserChangedEvent($user), UserChangedEvent::NAME);

        return $this;
    }

    public function isLoggedIn(): bool
    {
        try {
            if (!$this->currentUser) {
                $this->authSession();
            }
        } catch (Throwable $e) {
            $this->handleAuthStateFailure('Login state restore failed', $e);

            return false;
        }

        return $this->currentUser !== null;
    }

    public function clearCurrentUser(): void
    {
        $this->currentUser = null;
        $this->permissionsCache = null;
        $this->rolesCache = null;
        $this->highestPriority = null;
        $this->triedToLogin = false;
        $this->authFailureLogged = false;
        $this->permissionFailureLogged = false;
    }
}
