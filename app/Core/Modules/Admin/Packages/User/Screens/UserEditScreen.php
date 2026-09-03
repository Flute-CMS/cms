<?php

namespace Flute\Admin\Packages\User\Screens;

use Flute\Admin\Packages\User\Screens\Concerns\HandlesEmailActions;
use Flute\Admin\Packages\User\Screens\Concerns\HandlesSocialNetworks;
use Flute\Admin\Packages\User\Screens\Concerns\HandlesUserBlocks;
use Flute\Admin\Packages\User\Screens\Concerns\HandlesUserDangerActions;
use Flute\Admin\Packages\User\Screens\Concerns\HandlesUserMainTab;
use Flute\Admin\Packages\User\Screens\Concerns\HandlesUserSave;
use Flute\Admin\Packages\User\Screens\Concerns\HandlesUserTables;
use Flute\Admin\Packages\User\Services\AdminUsersService;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Core\Database\Entities\BalanceHistory;
use Flute\Core\Database\Entities\User;

class UserEditScreen extends Screen
{
    use HandlesEmailActions;
    use HandlesSocialNetworks;
    use HandlesUserBlocks;
    use HandlesUserDangerActions;
    use HandlesUserMainTab;
    use HandlesUserSave;
    use HandlesUserTables;

    public ?string $name = 'admin-users.title.edit';

    public ?string $description = 'admin-users.title.edit_description';

    public ?string $permission = 'admin.users';

    public ?User $user = null;

    public ?int $userId = null;

    public $blocksHistory;

    public $actionHistory;

    public $depositHistory;

    public $balanceHistory;

    public $socialNetworks;

    public $userDevices;

    public $usersService;

    public function mount(): void
    {
        $this->usersService = app(AdminUsersService::class);

        $this->userId = (int) ( request()->input('id') ?: $this->userId );

        if (!$this->userId) {
            $this->flashMessage(__('admin-users.messages.user_not_found'), 'error');
            $this->boostRedirect('/admin/users');

            return;
        }

        $this->initUser();

        if (!$this->user) {
            return;
        }

        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(
            __('admin-users.title.users'),
            url('/admin/users'),
        )->add($this->user->name ?? 'unknown');
    }

    public function layout(): array
    {
        if (!$this->user) {
            return [];
        }

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

                Tab::make(__('admin-users.tabs.balance_history'))
                    ->icon('ph.bold.clock-counter-clockwise-bold')
                    ->layouts([$this->balanceHistoryLayout()])
                    ->badge(sizeof($this->balanceHistory)),

                Tab::make(__('admin-users.tabs.action_history'))
                    ->icon('ph.bold.clock-counter-clockwise-bold')
                    ->layouts([$this->actionHistoryLayout()]),
            ])->slug('user-edit')->pills(),
        ];
    }

    protected function initUser(): void
    {
        $this->user = rep(User::class)->findByPK($this->userId);

        if (!$this->user) {
            $this->flashMessage(__('admin-users.messages.user_not_found'), 'error');
            $this->boostRedirect('/admin/users');

            return;
        }

        $this->blocksHistory = array_reverse($this->user->blocksReceived);
        $this->userDevices = $this->user->userDevices;
        $this->actionHistory = array_reverse($this->user->actionLogs);
        $this->depositHistory = array_reverse($this->user->invoices);
        $this->balanceHistory = BalanceHistory::query()
            ->where('user_id', '=', (string) $this->user->id)
            ->orderBy('created_at', 'DESC')
            ->fetchAll();
        $this->socialNetworks = $this->user->socialNetworks;

        $this->name = __('admin-users.title.edit', ['name' => $this->user->name]);
    }

    protected function canEditDisplayedUser(): bool
    {
        return $this->user instanceof User && user()->can($this->user);
    }
}
