<?php

namespace Flute\Core\Admin\Services;

use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\Role;
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
                'message' => __('admin.users.edit_permission_error'),
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
        $user->email = $input['email'];
        $user->login = $input['login'];
        $user->uri = $input['uri'];
        $user->balance = $input['balance'];

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

        transaction($user)->run();

        return ['status' => 'success', 'message' => 'User updated successfully', 'code' => 200];
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
                'message' => __('admin.users.delete_permission_error'),
                'code' => 403
            ];
        }

        // Фактическое удаление пользователя
        transaction($userToDelete, 'delete')->run();

        return ['status' => 'success', 'message' => __('admin.users.deleted_successfully'), 'code' => 200];
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
