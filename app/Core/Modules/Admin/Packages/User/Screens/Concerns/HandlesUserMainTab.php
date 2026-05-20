<?php

namespace Flute\Admin\Packages\User\Screens\Concerns;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Support\Color;

trait HandlesUserMainTab
{
    public function commandBar(): array
    {
        if (!$this->user) {
            return [];
        }

        $buttons = [
            Button::make(__('admin-users.buttons.to_profile'))
                ->type(Color::OUTLINE_PRIMARY)
                ->icon('ph.bold.arrow-up-right-bold')
                ->href(url('/profile/' . $this->user->getUrl())),

            Button::make(__('admin-users.buttons.cancel'))->type(Color::OUTLINE_PRIMARY)->redirect('/admin/users'),
        ];

        if ($this->canEditDisplayedUser()) {
            $buttons[] = Button::make(__('admin-users.buttons.save'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.floppy-disk-bold')
                ->method('saveUser');
        }

        return $buttons;
    }

    private function mainTabLayout()
    {
        $canEditUser = $this->canEditDisplayedUser();
        $isCurrentUser = user()->getCurrentUser()?->id === $this->user?->id;
        $canManageRoles = user()->can('admin.roles');
        $canResetPassword = user()->can('admin.users');
        $canManageSessions = user()->can('admin.users');
        $canDeleteUsers = user()->can('admin.users');

        return LayoutFactory::split([
            LayoutFactory::block([
                LayoutFactory::split([
                    LayoutFactory::field(
                        Input::make('avatar')
                            ->type('file')
                            ->filePond()
                            ->crop(1, 400, 400, true)
                            ->accept('image/png, image/jpeg, image/gif, image/webp')
                            ->defaultFile(asset($this->user?->avatar ?? config('profile.default_avatar')))
                            ->disabled(!$canEditUser),
                    )
                        ->label(__('admin-users.fields.avatar.label'))
                        ->small(__('admin-users.fields.avatar.help')),

                    LayoutFactory::field(
                        Input::make('banner')
                            ->type('file')
                            ->filePond()
                            ->crop(16 / 9, 1200, 675)
                            ->accept('image/png, image/jpeg, image/gif, image/webp')
                            ->defaultFile(asset($this->user?->banner ?? config('profile.default_banner')))
                            ->disabled(!$canEditUser),
                    )
                        ->label(__('admin-users.fields.banner.label'))
                        ->small(__('admin-users.fields.banner.help')),
                ]),

                LayoutFactory::split([
                    LayoutFactory::field(
                        Input::make('name')
                            ->type('text')
                            ->value($this->user?->name ?? '')
                            ->disabled(!$canEditUser),
                    )
                        ->label(__('admin-users.fields.name.label'))
                        ->required(),

                    LayoutFactory::field(
                        Input::make('login')
                            ->type('text')
                            ->value($this->user?->login ?? '')
                            ->disabled(!$canEditUser),
                    )
                        ->label(__('admin-users.fields.login.label'))
                        ->small(__('admin-users.fields.login.help')),
                ]),

                LayoutFactory::field(
                    Input::make('email')
                        ->type('email')
                        ->value($this->user?->email ?? '')
                        ->disabled(!$canEditUser),
                )
                    ->label(__('admin-users.fields.email.label'))
                    ->required(),

                ...(
                    $this->hasEmailActions()
                        ? [
                            LayoutFactory::view('admin-users::partials.email-actions', [
                                'user' => $this->user,
                            ]),
                        ] : []
                ),

                LayoutFactory::split([
                    LayoutFactory::field(
                        Input::make('uri')
                            ->type('text')
                            ->value($this->user?->uri ?? '')
                            ->disabled(!$canEditUser),
                    )
                        ->label(__('admin-users.fields.uri.label'))
                        ->small(__('admin-users.fields.uri.help')),

                    LayoutFactory::field(
                        Input::make('balance')
                            ->type('number')
                            ->step('0.01')
                            ->value($this->user?->balance ?? 0)
                            ->disabled(!$canEditUser),
                    )
                        ->label(__('admin-users.fields.balance.label'))
                        ->small(__('admin-users.fields.balance.help')),
                ]),

                LayoutFactory::field(Select::make('roles')
                    ->fromDatabase('roles', 'name', 'id', ['name', 'id', 'priority'])
                    ->multiple(true)
                    ->value($this->user?->roles)
                    ->placeholder(__('admin-users.fields.roles.placeholder'))
                    ->disabled(!$canManageRoles)
                    ->filter(static function ($role) {
                        if (user()->can('admin.boss')) {
                            return true;
                        }

                        return $role->priority < user()->getHighestPriority();
                    }))->label(__('admin-users.fields.roles.label')),

                LayoutFactory::split([
                    LayoutFactory::field(
                        ButtonGroup::make('verified')
                            ->options([
                                '0' => ['label' => __('def.no'), 'icon' => 'ph.bold.x-bold'],
                                '1' => ['label' => __('def.yes'), 'icon' => 'ph.bold.check-bold'],
                            ])
                            ->value($this->user?->verified ?? false ? '1' : '0')
                            ->disabled(!$canEditUser)
                            ->color('accent'),
                    )
                        ->label(__('admin-users.fields.verified.label'))
                        ->popover(__('admin-users.fields.verified.help')),

                    LayoutFactory::field(
                        ButtonGroup::make('hidden')
                            ->options([
                                '0' => ['label' => __('def.no'), 'icon' => 'ph.bold.eye-bold'],
                                '1' => ['label' => __('def.yes'), 'icon' => 'ph.bold.eye-slash-bold'],
                            ])
                            ->value($this->user?->hidden ?? false ? '1' : '0')
                            ->disabled(!$canEditUser)
                            ->color('accent'),
                    )
                        ->label(__('admin-users.fields.hidden.label'))
                        ->popover(__('admin-users.fields.hidden.help')),
                ]),

                ...(
                    user()->can('admin.boss')
                        ? [
                            LayoutFactory::field(
                                ButtonGroup::make('approved')
                                    ->options([
                                        '0' => ['label' => __('def.no'), 'icon' => 'ph.bold.x-bold'],
                                        '1' => ['label' => __('def.yes'), 'icon' => 'ph.bold.seal-check-bold'],
                                    ])
                                    ->value($this->user?->approved ?? false ? '1' : '0')
                                    ->color('accent'),
                            )
                                ->label(__('admin-users.fields.approved.label'))
                                ->popover(__('admin-users.fields.approved.help')),
                        ] : []
                ),
            ])->title(__('admin-users.sections.main_info')),

            LayoutFactory::rows([
                Button::make(
                    $this->user?->isBlocked() ? __('admin-users.buttons.unblock') : __('admin-users.buttons.block'),
                )
                    ->type($this->user?->isBlocked() ? Color::OUTLINE_SUCCESS : Color::OUTLINE_DANGER)
                    ->setVisible(!$isCurrentUser && $canEditUser)
                    ->icon($this->user?->isBlocked() ? 'ph.bold.lock-open-bold' : 'ph.bold.lock-bold')
                    ->method($this->user?->isBlocked() ? 'unblockUser' : 'blockUser')
                    ->fullWidth(),

                Button::make(__('admin-users.buttons.reset_password'))
                    ->type(Color::OUTLINE_PRIMARY)
                    ->icon('ph.bold.key-bold')
                    ->setVisible($canResetPassword)
                    ->modal('resetPasswordModal', ['userId' => $this->user?->id])
                    ->fullWidth(),

                Button::make(__('admin-users.buttons.clear_sessions'))
                    ->type(Color::OUTLINE_WARNING)
                    ->icon('ph.bold.sign-out-bold')
                    ->setVisible($canManageSessions && !$isCurrentUser)
                    ->method('clearUserSessions')
                    ->confirm(__('admin-users.confirms.clear_sessions'))
                    ->fullWidth(),

                Button::make(__('admin-users.buttons.delete_user'))
                    ->type(Color::OUTLINE_DANGER)
                    ->icon('ph.bold.trash-bold')
                    ->setVisible($canDeleteUsers && !$isCurrentUser)
                    ->method('deleteUser')
                    ->confirm(__('admin-users.confirms.delete_user'))
                    ->fullWidth(),
            ])->title(__('admin-users.sections.actions'))->description(__('admin-users.sections.actions_desc')),
        ])->ratio('70/30');
    }
}
