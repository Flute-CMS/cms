<?php

namespace Flute\Admin\Packages\User\Screens\Concerns;

use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\TextArea;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Core\Database\Entities\User;
use Throwable;

trait HandlesUserBlocks
{
    public function blockUser()
    {
        if (!$this->canEditDisplayedUser()) {
            $this->flashMessage(__('admin-users.messages.no_permission'), 'error');

            return;
        }

        if (user()->getCurrentUser()?->id === $this->user?->id) {
            $this->flashMessage(__('admin-users.messages.cant_self_block'), 'error');

            return;
        }

        $this->openModal('blockUserModal', ['userId' => $this->user->id]);
    }

    public function unblockUser()
    {
        if (!$this->canEditDisplayedUser()) {
            $this->flashMessage(__('admin-users.messages.no_permission'), 'error');

            return;
        }

        try {
            $this->usersService->unblockUser($this->user);
            $this->flashMessage(__('admin-users.messages.unblock_success'), 'success');
            $this->initUser();
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function blockUserModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(TextArea::make('reason')->placeholder(__(
                'admin-users.fields.block_reason.placeholder',
            )))
                ->label(__('admin-users.fields.block_reason.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('blockedUntil')
                    ->type('date')
                    ->placeholder(__('admin-users.fields.block_until.placeholder')),
            )
                ->label(__('admin-users.fields.block_until.label'))
                ->small(__('admin-users.fields.block_until.help')),
        ])
            ->title(__('admin-users.title.block_user'))
            ->applyButton(__('admin-users.buttons.block'))
            ->method('applyBlockUser');
    }

    public function applyBlockUser()
    {
        $data = request()->input();
        $userId = $this->modalParams->get('userId');

        $user = rep(User::class)->findByPK($userId);
        if (!$user) {
            $this->flashMessage(__('admin-users.messages.user_not_found'), 'error');

            return;
        }

        if (!user()->can($user)) {
            $this->flashMessage(__('admin-users.messages.no_permission'), 'error');

            return;
        }

        $validation = $this->validate([
            'reason' => ['required', 'string', 'max-str-len:500'],
            'blockedUntil' => ['nullable', 'date'],
        ], $data);

        if (!$validation) {
            return;
        }

        try {
            $this->usersService->blockUser($user, $data);
            $this->flashMessage(__('admin-users.messages.block_success'), 'success');
            $this->closeModal();
            $this->initUser();
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }
}
