<?php

namespace Flute\Admin\Packages\User\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Components\Cells\BadgeLink;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Fields\TextArea;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserActionLog;
use Flute\Core\Database\Entities\UserBlock;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Logging\LogRendererManager;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\Database\Entities\UserDevice;
use Flute\Admin\Packages\User\Services\AdminUsersService;

/**
 * Screen for adding and editing users.
 */
class UserEditScreen extends Screen
{
    public ?string $name = 'admin-users.title.edit';
    public ?string $description = 'admin-users.title.edit_description';
    public ?string $permission = 'admin.users';

    public ?User $user = null;
    public ?int $userId = null;
    public $blocksHistory;
    public $actionHistory;
    public $depositHistory;
    public $socialNetworks;
    public $userDevices;

    public $usersService;

    /**
     * Initialize the screen when loaded.
     */
    public function mount() : void
    {
        $this->usersService = app(AdminUsersService::class);

        $this->userId = (int) request()->input('id');

        if (! $this->userId) {
            $this->flashMessage(__('admin-users.messages.user_not_found'), 'error');
            $this->redirectTo('/admin/users', 300);
            return;
        }

        $this->initUser();

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-users.title.users'), url('/admin/users'))
            ->add($this->user->name ?? 'unknown');
    }

    protected function initUser() : void
    {
        $this->user = rep(User::class)->findByPK($this->userId);

        if (! $this->user) {
            $this->flashMessage(__('admin-users.messages.user_not_found'), 'error');
            $this->redirectTo('/admin/users', 300);
            return;
        }

        $this->blocksHistory = array_reverse($this->user->blocksReceived);
        $this->userDevices = $this->user->userDevices;
        $this->actionHistory = array_reverse($this->user->actionLogs);
        $this->depositHistory = array_reverse($this->user->invoices);
        $this->socialNetworks = $this->user->socialNetworks;

        $this->name = __('admin-users.title.edit', ['name' => $this->user->name]);
    }

    /**
     * Command bar with save button.
     */
    public function commandBar() : array
    {
        $buttons = [
            Button::make(__('admin-users.buttons.to_profile'))
                ->type(Color::OUTLINE_PRIMARY)
                ->icon('ph.bold.arrow-up-right-bold')
                ->href(url('/profile/'.$this->user->getUrl())),

            Button::make(__('admin-users.buttons.cancel'))
                ->type(Color::OUTLINE_PRIMARY)
                ->redirect('/admin/users'),
        ];

        if (user()->can($this->user)) {
            $buttons[] = Button::make(__('admin-users.buttons.save'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.floppy-disk-bold')
                ->method('saveUser');
        }

        return $buttons;
    }

    /**
     * Determine the layout of the screen using tabs.
     */
    public function layout() : array
    {
        return [
            LayoutFactory::tabs([
                Tab::make(__('admin-users.tabs.main'))
                    ->icon('ph.bold.user-bold')
                    ->layouts([$this->mainTabLayout()])
                    ->active(true),

                Tab::make(__('admin-users.tabs.sessions'))
                    ->icon('ph.bold.device-mobile-bold')
                    ->layouts([$this->sessionsLayout()])
                    ->badge(sizeof($this->userDevices)),

                Tab::make(__('admin-users.tabs.social_networks'))
                    ->icon('ph.bold.share-network-bold')
                    ->layouts([$this->socialNetworksLayout()])
                    ->badge(sizeof($this->socialNetworks)),

                Tab::make(__('admin-users.tabs.blocks_history'))
                    ->icon('ph.bold.shield-check-bold')
                    ->layouts([$this->blocksHistoryLayout()])
                    ->badge(sizeof($this->blocksHistory)),

                Tab::make(__('admin-users.tabs.deposit_history'))
                    ->icon('ph.bold.money-wavy-bold')
                    ->layouts([$this->depositHistoryLayout()])
                    ->badge(sizeof($this->depositHistory)),

                Tab::make(__('admin-users.tabs.action_history'))
                    ->icon('ph.bold.clock-counter-clockwise-bold')
                    ->layouts([$this->actionHistoryLayout()]),
            ])
                ->slug('user-edit')
                ->pills(),
        ];
    }

    /**
     * Main tab layout.
     */
    private function mainTabLayout()
    {
        $canEditUser = user()->can($this->user);
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
                            ->accept('image/png, image/jpeg, image/gif, image/webp')
                            ->defaultFile(asset($this->user?->avatar ?? config('profile.default_avatar')))
                            ->disabled(! $canEditUser)
                    )
                        ->label(__('admin-users.fields.avatar.label'))
                        ->small(__('admin-users.fields.avatar.help')),

                    LayoutFactory::field(
                        Input::make('banner')
                            ->type('file')
                            ->filePond()
                            ->accept('image/png, image/jpeg, image/gif, image/webp')
                            ->defaultFile(asset($this->user?->banner ?? config('profile.default_banner')))
                            ->disabled(! $canEditUser)
                    )
                        ->label(__('admin-users.fields.banner.label'))
                        ->small(__('admin-users.fields.banner.help')),
                ]),

                LayoutFactory::split([
                    LayoutFactory::field(
                        Input::make('name')
                            ->type('text')
                            ->value($this->user?->name ?? '')
                            ->disabled(! $canEditUser)
                    )
                        ->label(__('admin-users.fields.name.label'))
                        ->required(),

                    LayoutFactory::field(
                        Input::make('login')
                            ->type('text')
                            ->value($this->user?->login ?? '')
                            ->disabled(! $canEditUser)
                    )
                        ->label(__('admin-users.fields.login.label'))
                        ->small(__('admin-users.fields.login.help')),
                ]),

                LayoutFactory::field(
                    Input::make('email')
                        ->type('email')
                        ->value($this->user?->email ?? '')
                        ->disabled(! $canEditUser)
                )
                    ->label(__('admin-users.fields.email.label'))
                    ->required(),

                LayoutFactory::split([
                    LayoutFactory::field(
                        Input::make('uri')
                            ->type('text')
                            ->value($this->user?->uri ?? '')
                            ->disabled(! $canEditUser)
                    )
                        ->label(__('admin-users.fields.uri.label'))
                        ->small(__('admin-users.fields.uri.help')),

                    LayoutFactory::field(
                        Input::make('balance')
                            ->type('number')
                            ->step('0.01')
                            ->value($this->user?->balance ?? 0)
                            ->disabled(! $canEditUser)
                    )
                        ->label(__('admin-users.fields.balance.label'))
                        ->small(__('admin-users.fields.balance.help')),
                ]),

                LayoutFactory::field(
                    Select::make('roles')
                        ->fromDatabase('roles', 'name', 'id', ['name', 'id', 'priority'])
                        ->multiple(true)
                        ->value($this->user?->roles)
                        ->placeholder(__('admin-users.fields.roles.placeholder'))
                        ->disabled(! $canManageRoles)
                        ->filter(function ($role) {
                            if (user()->can('admin.boss')) {
                                return true;
                            }
                            return $role->priority < user()->getHighestPriority();
                        })
                )
                    ->label(__('admin-users.fields.roles.label')),

                LayoutFactory::split([
                    LayoutFactory::field(
                        Toggle::make('verified')
                            ->checked($this->user?->verified ?? false)
                            ->disabled(! $canEditUser)
                    )
                        ->label(__('admin-users.fields.verified.label'))
                        ->popover(__('admin-users.fields.verified.help')),

                    LayoutFactory::field(
                        Toggle::make('hidden')
                            ->checked($this->user?->hidden ?? false)
                            ->disabled(! $canEditUser)
                    )
                        ->label(__('admin-users.fields.hidden.label'))
                        ->popover(__('admin-users.fields.hidden.help')),
                ]),
            ])
                ->title(__('admin-users.sections.main_info')),

            LayoutFactory::rows([
                Button::make($this->user?->isBlocked() ? __('admin-users.buttons.unblock') : __('admin-users.buttons.block'))
                    ->type($this->user?->isBlocked() ? Color::OUTLINE_SUCCESS : Color::OUTLINE_DANGER)
                    ->setVisible(! $isCurrentUser && $canEditUser)
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
                    ->setVisible($canManageSessions && ! $isCurrentUser)
                    ->method('clearUserSessions')
                    ->confirm(__('admin-users.confirms.clear_sessions'))
                    ->fullWidth(),

                Button::make(__('admin-users.buttons.delete_user'))
                    ->type(Color::OUTLINE_DANGER)
                    ->icon('ph.bold.trash-bold')
                    ->setVisible($canDeleteUsers && ! $isCurrentUser)
                    ->method('deleteUser')
                    ->confirm(__('admin-users.confirms.delete_user'))
                    ->fullWidth(),
            ])
                ->title(__('admin-users.sections.actions'))
                ->description(__('admin-users.sections.actions_desc')),
        ])->ratio('70/30');
    }

    /**
     * Sessions tab layout.
     */
    private function sessionsLayout()
    {
        return LayoutFactory::table('userDevices', [
            TD::make('deviceDetails', __('admin-users.table.device'))
                ->render(fn (UserDevice $device) => $device->deviceDetails)
                ->width('300px'),

            TD::make('ip', __('admin-users.table.ip'))
                ->render(fn (UserDevice $device) => $device->ip)
                ->width('150px'),

            TD::make('actions', __('admin-users.table.actions'))
                ->render(fn (UserDevice $device) => $this->deviceActionsDropdown($device))
                ->width('100px'),
        ])
            ->searchable([
                'deviceDetails',
                'ip'
            ])
            ->commands([
                Button::make(__('admin-users.buttons.clear_sessions'))
                    ->type(Color::OUTLINE_DANGER)
                    ->icon('ph.bold.sign-out-bold')
                    ->method('clearUserSessions')
                    ->confirm(__('admin-users.confirms.clear_sessions'))
                    ->fullWidth(),
            ]);
    }

    /**
     * Device actions dropdown.
     */
    private function deviceActionsDropdown(UserDevice $device) : string
    {
        return DropDown::make()
            ->icon('ph.regular.dots-three-outline-vertical')
            ->list([
                DropDownItem::make(__('admin-users.buttons.terminate_session'))
                    ->confirm(__('admin-users.confirms.terminate_session'))
                    ->method('terminateSession', ['deviceId' => $device->id])
                    ->icon('ph.bold.sign-out-bold')
                    ->type(Color::OUTLINE_DANGER)
                    ->size('small')
                    ->fullWidth(),
            ]);
    }

    /**
     * Social networks tab layout.
     */
    private function socialNetworksLayout()
    {
        return LayoutFactory::table('socialNetworks', [
            TD::make('socialNetwork.key', __('admin-users.table.social_network'))
                ->render(fn (UserSocialNetwork $network) => $network->socialNetwork->key)
                ->width('200px'),

            TD::make('value', __('admin-users.table.value'))
                ->asComponent(BadgeLink::class, [
                    'url' => ':url',
                ])
                ->width('200px'),

            TD::make('name', __('admin-users.table.display_name'))
                ->render(fn (UserSocialNetwork $network) => $network->name ?? '-')
                ->width('200px'),

            TD::make('linkedAt', __('admin-users.table.link_date'))
                ->render(fn (UserSocialNetwork $network) => $network->linkedAt->format('d.m.Y H:i'))
                ->width('200px'),

            TD::make('hidden', __('admin-users.table.visibility'))
                ->render(
                    fn (UserSocialNetwork $network) =>
                    view('admin-users::cells.visibility-badge', ['visible' => ! $network->hidden])
                )
                ->width('100px'),

            TD::make('actions', __('admin-users.table.actions'))
                ->render(fn (UserSocialNetwork $network) => $this->socialNetworkActionsDropdown($network))
                ->width('100px'),
        ])
            ->searchable([
                'socialNetwork.key',
                'value',
                'name',
                'url'
            ])
            ->commands([
                Button::make(__('admin-users.buttons.add_social'))
                    ->type(Color::OUTLINE_PRIMARY)
                    ->icon('ph.bold.plus-bold')
                    ->modal('addSocialNetworkModal')
                    ->fullWidth(),
            ]);
    }

    /**
     * Blocks history tab layout.
     */
    private function blocksHistoryLayout()
    {
        return
            LayoutFactory::table('blocksHistory', [
                TD::make('reason', __('admin-users.table.reason'))
                    ->render(fn (UserBlock $block) => $block->reason)
                    ->width('300px'),

                TD::make('blockedBy.name', __('admin-users.table.blocked_by'))
                    ->render(fn (UserBlock $block) => "<a class='badge ghost-primary' href='".url('admin/users/'.$block->blockedBy->id . '/edit')."'>{$block->blockedBy->name}</a>")
                    ->width('200px'),

                TD::make('blockedFrom', __('admin-users.table.blocked_from'))
                    ->render(fn (UserBlock $block) => $block->blockedFrom->format('d.m.Y H:i'))
                    ->width('200px'),

                TD::make('blockedUntil', __('admin-users.table.blocked_until'))
                    ->render(fn (UserBlock $block) => $block->blockedUntil ? $block->blockedUntil->format('d.m.Y H:i') : 'Навсегда')
                    ->width('200px'),
            ]);
    }

    /**
     * Action history tab layout.
     */
    private function actionHistoryLayout()
    {
        return
            LayoutFactory::table('actionHistory', [
                TD::make('details', __('admin-users.table.details'))
                    ->render(fn (UserActionLog $log) => app(LogRendererManager::class)->render($log))
                    ->width('400px'),
            ]);
    }

    /**
     * Deposit history tab layout.
     */
    private function depositHistoryLayout()
    {
        return
            LayoutFactory::table('depositHistory', [
                TD::make('transactionId', __('admin-users.table.transaction_id'))
                    ->render(fn (PaymentInvoice $invoice) => $invoice->transactionId)
                    ->width('200px'),

                TD::make('gateway', __('admin-users.table.payment_gateway'))
                    ->render(fn (PaymentInvoice $invoice) => $invoice->gateway)
                    ->width('200px'),

                TD::make('amount', __('admin-users.table.amount'))
                    ->render(fn (PaymentInvoice $invoice) => number_format($invoice->amount, 2).' '.$invoice->currency->code)
                    ->width('150px'),

                TD::make('isPaid', __('admin-users.table.status'))
                    ->render(fn (PaymentInvoice $invoice) => view('admin-users::cells.payment-status', ['invoice' => $invoice]))
                    ->width('150px'),

                TD::make('paidAt', __('admin-users.table.payment_date'))
                    ->render(fn (PaymentInvoice $invoice) => $invoice->paidAt ? $invoice->paidAt->format('d.m.Y H:i') : '-')
                    ->width('200px'),
            ]);
    }

    /**
     * Save user (create or update).
     */
    public function saveUser()
    {
        if (! user()->can($this->user)) {
            $this->flashMessage(__('admin-users.messages.no_permission'), 'error');
            return;
        }

        $data = request()->input();
        $files = request()->files;

        if (isset($data['roles']) && ! user()->can('admin.roles')) {
            $this->flashMessage(__('admin-users.messages.no_permission_roles'), 'error');
            return;
        }

        $validation = $this->validate([
            'name' => ['required', 'string', 'max-str-len:255'],
            'login' => ['nullable', 'string', 'max-str-len:255', 'unique:users,login,'.$this->userId],
            'uri' => ['nullable', 'string', 'max-str-len:255', 'unique:users,uri,'.$this->userId],
            'email' => ['nullable', 'email', 'max-str-len:255', 'unique:users,email,'.$this->userId],
            'avatar' => ['nullable', 'image', 'max-file-size:10'],
            'banner' => ['nullable', 'image', 'max-file-size:10'],
            'balance' => ['required', 'numeric', 'min:0'],
            'roles' => ['nullable', 'array'],
            'verified' => ['sometimes', 'boolean'],
            'hidden' => ['sometimes', 'boolean'],
        ], $data);

        if (! $validation) {
            return;
        }

        try {
            $this->usersService->saveUser($this->user, $data, $files);
            $this->flashMessage(__('admin-users.messages.save_success'), 'success');
            $this->initUser();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Block user.
     */
    public function blockUser()
    {
        if (! user()->can($this->user)) {
            $this->flashMessage(__('admin-users.messages.no_permission'), 'error');
            return;
        }

        if (user()->getCurrentUser()?->id === $this->user?->id) {
            $this->flashMessage(__('admin-users.messages.cant_self_block'), 'error');
            return;
        }

        $this->openModal('blockUserModal', ['userId' => $this->user->id]);
    }

    /**
     * Unblock user.
     */
    public function unblockUser()
    {
        if (! user()->can($this->user)) {
            $this->flashMessage(__('admin-users.messages.no_permission'), 'error');
            return;
        }

        try {
            $this->usersService->unblockUser($this->user);
            $this->flashMessage(__('admin-users.messages.unblock_success'), 'success');
            $this->initUser();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Block user modal.
     */
    public function blockUserModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                TextArea::make('reason')
                    ->placeholder(__('admin-users.fields.block_reason.placeholder'))
            )
                ->label(__('admin-users.fields.block_reason.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('blockedUntil')
                    ->type('date')
                    ->placeholder(__('admin-users.fields.block_until.placeholder'))
            )
                ->label(__('admin-users.fields.block_until.label'))
                ->small(__('admin-users.fields.block_until.help')),
        ])
            ->title(__('admin-users.title.block_user'))
            ->applyButton(__('admin-users.buttons.block'))
            ->method('applyBlockUser');
    }

    /**
     * Apply user block.
     */
    public function applyBlockUser()
    {
        $data = request()->input();
        $userId = $this->modalParams->get('userId');

        $user = rep(User::class)->findByPK($userId);
        if (! $user) {
            $this->flashMessage(__('admin-users.messages.user_not_found'), 'danger');
            return;
        }

        $validation = $this->validate([
            'reason' => ['required', 'string', 'max-str-len:500'],
            'blockedUntil' => ['nullable', 'date'],
        ], $data);

        if (! $validation) {
            return;
        }

        try {
            $this->usersService->blockUser($user, $data);
            $this->flashMessage(__('admin-users.messages.block_success'), 'success');
            $this->closeModal();
            $this->initUser();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Social network actions dropdown.
     */
    private function socialNetworkActionsDropdown(UserSocialNetwork $network) : string
    {
        return DropDown::make()
            ->icon('ph.regular.dots-three-outline-vertical')
            ->list([
                DropDownItem::make(__('admin-users.buttons.edit'))
                    ->modal('editSocialNetworkModal', ['networkId' => $network->id])
                    ->icon('ph.bold.pencil-bold')
                    ->type(Color::OUTLINE_PRIMARY)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make($network->hidden ? __('admin-users.buttons.show') : __('admin-users.buttons.hide'))
                    ->method('toggleSocialNetworkVisibility', ['networkId' => $network->id])
                    ->icon($network->hidden ? 'ph.bold.eye-bold' : 'ph.bold.eye-slash-bold')
                    ->type(Color::OUTLINE_WARNING)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make(__('admin-users.buttons.delete'))
                    ->confirm(__('admin-users.confirms.delete_social_network'))
                    ->method('deleteSocialNetwork', ['networkId' => $network->id])
                    ->icon('ph.bold.trash-bold')
                    ->type(Color::OUTLINE_DANGER)
                    ->size('small')
                    ->fullWidth(),
            ]);
    }

    /**
     * Add social network modal.
     */
    public function addSocialNetworkModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Select::make('socialNetwork')
                    ->preload()
                    ->fromDatabase('socials', 'key', 'id', ['key', 'id'])
                    ->placeholder(__('admin-users.fields.social_network.placeholder'))
            )
                ->label(__('admin-users.fields.social_network.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('value')
                    ->type('text')
                    ->placeholder(__('admin-users.fields.social_value.placeholder'))
            )
                ->label(__('admin-users.fields.social_value.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('url')
                    ->type('url')
                    ->placeholder(__('admin-users.fields.social_url.placeholder'))
            )
                ->label(__('admin-users.fields.social_url.label')),

            LayoutFactory::field(
                Input::make('name')
                    ->type('text')
                    ->placeholder(__('admin-users.fields.social_name.placeholder'))
            )
                ->label(__('admin-users.fields.social_name.label')),
        ])
            ->title(__('admin-users.title.add_social_network'))
            ->applyButton(__('admin-users.buttons.add_social'))
            ->method('addSocialNetwork');
    }

    /**
     * Add social network.
     */
    public function addSocialNetwork()
    {
        $data = request()->input();

        $validation = $this->validate([
            'socialNetwork' => ['required', 'integer', 'exists:social_networks,id'],
            'value' => ['required', 'string', 'max-str-len:255'],
            'url' => ['nullable', 'url', 'max-str-len:255'],
            'name' => ['nullable', 'string', 'max-str-len:255'],
        ], $data);

        if (! $validation) {
            return;
        }

        try {
            $this->usersService->addSocialNetwork($this->user, $data);
            $this->flashMessage(__('admin-users.messages.social_added'), 'success');
            $this->closeModal();
            $this->initUser();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Edit social network modal.
     */
    public function editSocialNetworkModal(Repository $parameters)
    {
        $networkId = $parameters->get('networkId');
        $network = rep(UserSocialNetwork::class)->findByPK($networkId);

        if (! $network) {
            $this->flashMessage(__('admin-users.messages.social_not_found'), 'danger');
            return;
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('value')
                    ->type('text')
                    ->value($network->value)
                    ->placeholder(__('admin-users.fields.social_value.placeholder'))
            )
                ->label(__('admin-users.fields.social_value.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('url')
                    ->type('url')
                    ->value($network->url)
                    ->placeholder(__('admin-users.fields.social_url.placeholder'))
            )
                ->label(__('admin-users.fields.social_url.label')),

            LayoutFactory::field(
                Input::make('name')
                    ->type('text')
                    ->value($network->name)
                    ->placeholder(__('admin-users.fields.social_name.placeholder'))
            )
                ->label(__('admin-users.fields.social_name.label')),
        ])
            ->title(__('admin-users.title.edit_social_network'))
            ->applyButton(__('admin-users.buttons.save_social'))
            ->method('updateSocialNetwork');
    }

    /**
     * Update social network.
     */
    public function updateSocialNetwork()
    {
        $data = request()->input();
        $networkId = $this->modalParams->get('networkId');

        $validation = $this->validate([
            'value' => ['required', 'string', 'max-str-len:255'],
            'url' => ['nullable', 'url', 'max-str-len:255'],
            'name' => ['nullable', 'string', 'max-str-len:255'],
        ], $data);

        if (! $validation) {
            return;
        }

        try {
            $this->usersService->updateSocialNetwork($networkId, $data);
            $this->flashMessage(__('admin-users.messages.social_updated'), 'success');
            $this->closeModal();
            $this->initUser();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Toggle social network visibility.
     */
    public function toggleSocialNetworkVisibility()
    {
        $networkId = request()->input('networkId');

        try {
            $this->usersService->toggleSocialNetworkVisibility($networkId);
            $this->flashMessage(__('admin-users.messages.social_visibility_changed'), 'success');
            $this->initUser();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Delete social network.
     */
    public function deleteSocialNetwork()
    {
        $networkId = request()->input('networkId');

        try {
            $this->usersService->deleteSocialNetwork($networkId);
            $this->flashMessage(__('admin-users.messages.social_deleted'), 'success');
            $this->initUser();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Clear user sessions.
     */
    public function clearUserSessions()
    {
        if (! user()->can('admin.users')) {
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
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Delete user.
     */
    public function deleteUser()
    {
        if (! user()->can('admin.users')) {
            $this->flashMessage(__('admin-users.messages.no_permission_delete'), 'error');
            return;
        }

        if (user()->getCurrentUser()?->id === $this->user?->id) {
            $this->flashMessage(__('admin-users.messages.cant_self_delete'), 'error');
            return;
        }

        if (!user()->can($this->user)) {
            $this->flashMessage(__('admin-users.messages.no_permission_delete'), 'error');
            return;
        }

        $this->user->delete();
        $this->flashMessage(__('admin-users.messages.delete_success'), 'success');
        $this->redirectTo('/admin/users');
    }

    /**
     * Reset password modal.
     */
    public function resetPasswordModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('password')
                    ->type('password')
                    ->placeholder(__('admin-users.fields.password.placeholder'))
            )
                ->label(__('admin-users.fields.password.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('password_confirmation')
                    ->type('password')
                    ->placeholder(__('admin-users.fields.password.confirm_placeholder'))
            )
                ->label(__('admin-users.fields.password.confirm_label'))
                ->required(),
        ])
            ->title(__('admin-users.title.reset_password'))
            ->applyButton(__('admin-users.buttons.reset_password'))
            ->method('applyResetPassword');
    }

    /**
     * Apply password reset.
     */
    public function applyResetPassword()
    {
        if (! user()->can('admin.users')) {
            $this->flashMessage(__('admin-users.messages.no_permission_reset_password'), 'error');
            return;
        }

        $data = request()->input();

        $validation = $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ], $data);

        if (! $validation) {
            return;
        }

        try {
            $this->usersService->resetPassword($this->user, $data['password']);
            $this->flashMessage(__('admin-users.messages.password_reset_success'), 'success');
            $this->closeModal();
            $this->initUser();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }
}
