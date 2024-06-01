<?php

namespace Flute\Core\Admin\Services;

use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\UserBlock;
use Nette\Utils\Validators;
use Nette\Utils\AssertionException;

class UserService
{
    /**
     * Edit a user's details.
     *
     * @param array $input Input data
     * @param string $userId User ID
     * @return array Result of the operation
     */
    public function editUser(array $input, string $userId): array
    {
        /** @var User */
        $user = rep(User::class)->findByPK((int) $userId);

        if (!$user) {
            return ['status' => 'error', 'message' => __('admin.users.not_found'), 'code' => 404];
        }

        try {
            $this->validateUserInput($input);
        } catch (AssertionException $e) {
            return ['status' => 'error', 'message' => $e->getMessage(), 'code' => 400];
        }

        $currentUser = user()->getCurrentUser();

        if (!$this->canEditUser($currentUser, $user)) {
            return [
                'status' => 'error',
                'message' => __('admin.users.permission_error'),
                'code' => 403
            ];
        }

        $inputRoles = array_column($input['selectedRoles'] ?? [], 'id');
        $highestPriorityRole = $this->getHighestPriorityRole(user()->getCurrentUser());

        if ($highestPriorityRole && !in_array($highestPriorityRole->id, $inputRoles) && !user()->hasPermission('admin.boss')) {
            return ['status' => 'error', 'message' => __('admin.users.cannot_remove_highest_role'), 'code' => 403];
        }

        // Update user fields
        $user->name = $input['name'];
        $user->uri = empty($input['uri']) ? null : $input['uri'];
        $user->balance = $input['balance'];
        $user->login = empty($input['login']) ? null : $input['login'];
        $user->email = empty($input['email']) ? null : $input['email'];

        $currentUserHighestPriority = $this->getCurrentUserHighestPriority(user()->getCurrentUser());
        $user->clearRoles();

        foreach ($input['selectedRoles'] as $roleInput) {
            $roleToAdd = rep(Role::class)->findByPK($roleInput['id']);
            if ($roleToAdd) {
                if ($roleToAdd->priority > $currentUserHighestPriority && !user()->hasPermission('admin.boss')) {
                    return [
                        'status' => 'error',
                        'message' => __('admin.users.role_priority_error', ['role' => $roleToAdd->name]),
                        'code' => 403
                    ];
                }
                $user->addRole($roleToAdd);
            }
        }

        user()->log('events.user_edited', $user->id);

        transaction($user)->run();

        return ['status' => 'success', 'message' => __('def.success'), 'code' => 200];
    }

    /**
     * Delete a user.
     *
     * @param string $userId User ID to delete
     * @param User $currentUser Current logged-in user
     * @return array Result of the operation
     */
    public function deleteUser(string $userId, User $currentUser): array
    {
        $userToDelete = rep(User::class)->findByPK((int) $userId);

        if (!$userToDelete) {
            return ['status' => 'error', 'message' => __('admin.users.not_found'), 'code' => 404];
        }

        if ($currentUser->id === $userToDelete->id) {
            return ['status' => 'error', 'message' => __('admin.users.cannot_delete_self'), 'code' => 403];
        }

        if (!$this->canEditUser($currentUser, $userToDelete)) {
            return [
                'status' => 'error',
                'message' => __('admin.users.permission_error'),
                'code' => 403
            ];
        }

        user()->log('events.user_deleted', $userToDelete->id);

        // Фактическое удаление пользователя
        transaction($userToDelete, 'delete')->run();

        return ['status' => 'success', 'message' => __('admin.users.deleted_successfully'), 'code' => 200];
    }

    /**
     * Ban a user.
     *
     * @param int $userId User ID
     * @param int $duration Duration of the ban in seconds
     * @param string $reason Reason for the ban
     * @param User $currentUser Current logged-in user
     * @return array Result of the operation
     */
    public function banUser(int $userId, int $duration, string $reason, User $currentUser): array
    {
        $userToBan = rep(User::class)->findByPK($userId);

        if (!$userToBan) {
            return ['status' => 'error', 'message' => __('admin.users.not_found'), 'code' => 404];
        }

        if (!$this->canEditUser($currentUser, $userToBan)) {
            return [
                'status' => 'error',
                'message' => __('admin.users.permission_error'),
                'code' => 403
            ];
        }

        $block = new UserBlock();
        $block->user = $userToBan;
        $block->blockedBy = $currentUser;
        $block->reason = $reason;
        $block->blockedFrom = new \DateTime();
        $block->blockedUntil = $duration > 0 ? (new \DateTime())->modify("+{$duration} seconds") : null;

        transaction($block)->run();

        user()->log('events.user_banned', $userToBan->id);

        return ['status' => 'success', 'message' => __('admin.users.banned_successfully'), 'code' => 200];
    }

    /**
     * Unblock a user.
     *
     * @param int $userId User ID
     * @param User $currentUser Current logged-in user
     * @return array Result of the operation
     */
    public function unblockUser(int $userId, User $currentUser): array
    {
        $userToUnblock = rep(User::class)->findByPK($userId);

        if (!$userToUnblock) {
            return ['status' => 'error', 'message' => __('admin.users.not_found'), 'code' => 404];
        }

        if (!$this->canEditUser($currentUser, $userToUnblock)) {
            return [
                'status' => 'error',
                'message' => __('admin.users.permission_error'),
                'code' => 403
            ];
        }

        foreach ($userToUnblock->blocksReceived as $block) {
            if ($block->blockedUntil === null || $block->blockedUntil > new \DateTime()) {
                transaction($block, 'delete')->run();
            }
        }

        user()->log('events.user_unbanned', $userToUnblock->id);

        return ['status' => 'success', 'message' => __('admin.users.unblocked_successfully'), 'code' => 200];
    }

    /**
     * Give money to a user.
     *
     * @param int $userId User ID
     * @param float $amount Amount to give
     * @param User $currentUser Current logged-in user
     * @return array Result of the operation
     */
    public function giveMoney(int $userId, float $amount, User $currentUser): array
    {
        $user = rep(User::class)->findByPK($userId);

        if (!$user) {
            return ['status' => 'error', 'message' => __('admin.users.not_found'), 'code' => 404];
        }

        if (!$this->canEditUser($currentUser, $user)) {
            return ['status' => 'error', 'message' => __('admin.users.permission_error'), 'code' => 403];
        }

        if ($amount <= 0) {
            return ['status' => 'error', 'message' => __('admin.users.invalid_amount'), 'code' => 400];
        }

        $user->balance += $amount;

        transaction($user)->run();

        return ['status' => 'success', 'message' => __('admin.users.money_given_successfully'), 'code' => 200];
    }

    /**
     * Take money from a user.
     *
     * @param int $userId User ID
     * @param float $amount Amount to take
     * @param User $currentUser Current logged-in user
     * @return array Result of the operation
     */
    public function takeMoney(int $userId, float $amount, User $currentUser): array
    {
        $user = rep(User::class)->findByPK($userId);

        if (!$user) {
            return ['status' => 'error', 'message' => __('admin.users.not_found'), 'code' => 404];
        }

        if (!$this->canEditUser($currentUser, $user)) {
            return ['status' => 'error', 'message' => __('admin.users.permission_error'), 'code' => 403];
        }

        if ($amount <= 0 || $amount > $user->balance) {
            return ['status' => 'error', 'message' => __('admin.users.invalid_amount'), 'code' => 400];
        }

        $user->balance -= $amount;

        transaction($user)->run();

        return ['status' => 'success', 'message' => __('admin.users.money_taken_successfully'), 'code' => 200];
    }

    public function getHighestPriorityRole(User $user): ?Role
    {
        $highestPriority = null;
        $highestPriorityRole = null;

        foreach ($user->getRoles() as $role) {
            if (is_null($highestPriority) || $role->priority > $highestPriority) {
                $highestPriority = $role->priority;
                $highestPriorityRole = $role;
            }
        }

        return $highestPriorityRole;
    }

    public function getCurrentUserHighestPriority(User $user): int
    {
        $highestPriority = 0;
        foreach ($user->getRoles() as $role) {
            if ($role->priority > $highestPriority) {
                $highestPriority = $role->priority;
            }
        }
        return $highestPriority;
    }

    /**
     * Check if the current user can edit the given user.
     *
     * @param User $currentUser
     * @param User $userToEdit
     * @return bool
     */
    public function canEditUser(User $currentUser, User $userToEdit): bool
    {
        $currentUserHighestPriority = $this->getCurrentUserHighestPriority($currentUser);
        $userToEditHighestPriority = $this->getCurrentUserHighestPriority($userToEdit);

        return $currentUserHighestPriority > $userToEditHighestPriority || user()->hasPermission('admin.boss');
    }

    private function validateUserInput(array $input)
    {
        // Validate 'name'
        if (!isset($input['name']) || !preg_match('/^[a-zA-Z0-9\s\p{L}\p{M},.;:\'"\[\]()\-]+$/u', $input['name'])) {
            throw new AssertionException(__('profile.name_error'));
        }

        // Validate 'email' if present
        if (isset($input['email']) && !empty($input['email'])) {
            Validators::assert($input['email'], 'email');
        }

        // Validate 'balance'
        if (isset($input['balance'])) {
            Validators::assert($input['balance'], 'numeric');
        }

        // Validate 'login' if present
        if (isset($input['login']) && !empty($input['login']) && !preg_match('/^[a-zA-Z0-9]*$/', $input['login'])) {
            throw new AssertionException(__('admin.users.login_error'));
        }

        // Validate 'uri' if present
        if (isset($input['uri']) && !empty($input['uri']) && !preg_match('/^[a-zA-Z0-9_-]+$/', $input['uri'])) {
            throw new AssertionException(__('profile.uri_error'));
        }

        // Validate 'selectedRoles' if present
        if (isset($input['selectedRoles']) && is_array($input['selectedRoles'])) {
            foreach ($input['selectedRoles'] as $role) {
                Validators::assert($role['id'], 'numeric');
                Validators::assert($role['name'], 'string:1..255');
                Validators::assert($role['color'], 'string:0..7');
            }
        }
    }

}
