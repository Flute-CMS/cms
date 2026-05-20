<?php

namespace Flute\Admin\Packages\User\Screens\Concerns;

use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Throwable;

trait HandlesUserDangerActions
{
    public function clearUserSessions()
    {
        if (!user()->can('admin.users')) {
            $this->flashMessage(__('admin-users.messages.no_permission_sessions'), 'error');

            return;
        }

        if (user()->getCurrentUser()?->id === $this->user?->id) {
            $this->flashMessage(__('admin-users.messages.cant_self_clear_sessions'), 'error');

            return;
        }

        try {
            $this->usersService->clearUserSessions($this->user);
            $this->flashMessage(__('admin-users.messages.sessions_cleared'), 'success');
            $this->initUser();
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function deleteUser()
    {
        if (!user()->can('admin.users')) {
            $this->flashMessage(__('admin-users.messages.no_permission_delete'), 'error');

            return;
        }

        if (user()->getCurrentUser()?->id === $this->user?->id) {
            $this->flashMessage(__('admin-users.messages.cant_self_delete'), 'error');

            return;
        }

        if (!$this->canEditDisplayedUser()) {
            $this->flashMessage(__('admin-users.messages.no_permission_delete'), 'error');

            return;
        }

        $this->user->delete();
        $this->flashMessage(__('admin-users.messages.delete_success'), 'success');
        $this->redirectTo('/admin/users');
    }

    public function resetPasswordModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('password')->type('password')->placeholder(__('admin-users.fields.password.placeholder')),
            )
                ->label(__('admin-users.fields.password.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('password_confirmation')
                    ->type('password')
                    ->placeholder(__('admin-users.fields.password.confirm_placeholder')),
            )
                ->label(__('admin-users.fields.password.confirm_label'))
                ->required(),
        ])
            ->title(__('admin-users.title.reset_password'))
            ->applyButton(__('admin-users.buttons.reset_password'))
            ->method('applyResetPassword');
    }

    public function applyResetPassword()
    {
        if (!user()->can('admin.users')) {
            $this->flashMessage(__('admin-users.messages.no_permission_reset_password'), 'error');

            return;
        }

        $data = request()->input();

        $validation = $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ], $data);

        if (!$validation) {
            return;
        }

        try {
            $this->usersService->resetPassword($this->user, $data['password']);
            $this->flashMessage(__('admin-users.messages.password_reset_success'), 'success');
            $this->closeModal();
            $this->initUser();
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }
}
