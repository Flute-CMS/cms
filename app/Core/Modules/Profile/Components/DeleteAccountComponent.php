<?php

namespace Flute\Core\Modules\Profile\Components;

use Exception;
use Flute\Core\Database\Entities\User;
use Flute\Core\Support\FluteComponent;

class DeleteAccountComponent extends FluteComponent
{
    public ?string $delete_confirmation = null;

    public ?User $user = null;

    public function mount()
    {
        $this->user = user()->getCurrentUser();
    }

    public function render()
    {
        return $this->view('flute::components.profile-tabs.edit.delete-account', [
            'user' => $this->user,
        ]);
    }

    public function deleteAccount()
    {
        if ($this->validateDeleteAccount()) {
            if (!$this->confirmed('delete_account_confirmation')) {
                $this->confirm(
                    actionKey: 'delete_account_confirmation',
                    message: __('profile.edit.main.delete_account.confirm_message'),
                    type: 'error',
                    confirmText: __('def.delete'),
                    cancelText: __('def.cancel'),
                );

                return;
            }

            try {
                user()->deleteUser($this->user);

                auth()->logout();

                $this->flashMessage(__('profile.edit.main.delete_account.delete_success'), 'success');
            } catch (Exception $e) {
                $this->inputError('delete_confirmation', __('profile.edit.main.delete_account.delete_failed'));
            }
        }
    }

    protected function validateDeleteAccount(): bool
    {
        return $this->validate(
            [
                'delete_confirmation' => 'required|in:' . $this->user->login,
            ],
            null,
            [
                'delete_confirmation.in' => __('profile.edit.main.delete_account.confirmation_error'),
            ],
        );
    }
}
