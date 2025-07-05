<?php

namespace Flute\Admin\Packages\User\Screens;

use Carbon\Carbon;
use Cycle\ORM\Select\JoinableLoader;
use Flute\Admin\Packages\User\Services\AdminUsersService;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\User;

class UserListScreen extends Screen
{
    protected string $name = 'admin-users.title.users';
    protected ?string $description = 'admin-users.title.description';
    protected $permission = 'admin.users';
    public $users;
    public $blockedUsers;

    public function mount() : void
    {
        breadcrumb()->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-users.title.users'));

        $this->users = rep(User::class)
            ->select()
            ->distinct()
            ->with('blocksReceived', [
                'method' => JoinableLoader::LEFT_JOIN,
            ])
            ->where(function ($qb) {
                $qb->where('blocksReceived.id', null)
                    ->orWhere('blocksReceived.isActive', false)
                    ->orWhere('blocksReceived.blockedUntil', '<', new \DateTimeImmutable());
            });

        $this->blockedUsers = rep(User::class)
            ->select()
            ->with('blocksReceived')
            ->where('blocksReceived.isActive', true)
            ->where(function ($qb) {
                $qb->where('blocksReceived.blockedUntil', '>', new \DateTimeImmutable())
                    ->orWhere('blocksReceived.blockedUntil', null);
            })
            ->orderBy('id', 'desc');
    }

    public function layout() : array
    {
        return [
            LayoutFactory::tabs([
                Tab::make(__('admin-users.tabs.all'))->badge($this->users->count())
                    ->layouts([
                        LayoutFactory::table('users', [
                            TD::make('name', __('admin-users.table.user'))
                                ->width('250px')
                                ->render(function (User $user) {
                                    return view('admin-users::cells.user', compact('user'));
                                })
                                ->cantHide(),

                            TD::make('balance', __('admin-users.table.balance'))
                                ->width('100px')
                                ->sort()
                                ->disableSearch()
                                ->render(function (User $user) {
                                    return user()->can($user) ? number_format($user->balance, 2).' '.config('lk.currency_view') : '—';
                                }),

                            TD::make('createdAt', __('admin-users.table.registration_date'))
                                ->width('150px')
                                ->sort()
                                ->defaultSort(true, 'desc')
                                ->disableSearch()
                                ->render(function (User $user) {
                                    return "<span title='".(new Carbon($user->createdAt))->format('d.m.Y H:i')."'>".(new Carbon($user->createdAt))->diffForHumans()."</span>";
                                }),

                            TD::make('last_logged', __('admin-users.table.status'))
                                ->width('140px')
                                ->sort()
                                ->render(function (User $user) {
                                    return view('admin-users::cells.user-status', compact('user'));
                                }),

                            TD::make(__('admin-users.table.actions'))
                                ->align(TD::ALIGN_CENTER)
                                ->disableSearch()
                                ->width('100px')
                                ->cantHide()
                                ->render(function (User $user) {
                                    return user()->can($user) ? DropDown::make()
                                        ->icon('ph.regular.dots-three-outline-vertical')
                                        ->list([
                                            DropDownItem::make(__('admin-users.buttons.edit'))
                                                ->type(Color::OUTLINE_PRIMARY)
                                                ->icon('ph.regular.pencil')
                                                ->size('small')
                                                ->fullWidth()
                                                ->redirect(url('admin/users/'.$user->id.'/edit')),

                                            DropDownItem::make(__('admin-users.buttons.delete'))
                                                ->fullWidth()
                                                ->confirm(__('admin-users.confirms.delete_user'))
                                                ->type(Color::OUTLINE_DANGER)
                                                ->icon('ph.regular.trash')
                                                ->size('small')
                                                ->method("deleteUser", [
                                                    "id" => $user->id,
                                                ]),
                                        ]) : null;
                                }),
                        ])->perPage(10)->searchable(['name', 'email', 'login'])
                    ]),

                Tab::make(__('admin-users.tabs.blocked'))
                    ->badge($this->blockedUsers->count())
                    ->layouts([
                        LayoutFactory::table('blockedUsers', [
                            TD::make('name', __('admin-users.table.user'))
                                ->width('250px')
                                ->render(function (User $user) {
                                    return view('admin-users::cells.user', compact('user'));
                                })
                                ->cantHide(),

                            TD::make('block_info', __('admin-users.table.block_info'))
                                ->width('300px')
                                ->render(function (User $item) {
                                    $blockInfo = $item->getBlockInfo();
                                    if ($blockInfo) {
                                        $blockedUntil = $blockInfo['blockedUntil'] ? $blockInfo['blockedUntil']->format('Y-m-d H:i') : __('admin-users.status.forever');
                                        return __('admin-users.status.blocked_until', ['date' => $blockedUntil]).'<br>'.__('admin-users.status.block_reason', ['reason' => htmlspecialchars($blockInfo['reason'])]);
                                    }
                                    return '—';

                                })
                                ->cantHide(),

                            TD::make('blocksReceived.blockedFrom', __('admin-users.table.blocked_from'))
                                ->width('150px')
                                ->sort()
                                ->disableSearch()
                                ->render(function (User $user) {
                                    return isset($user->getBlockInfo()['blockedFrom']) ? $user->getBlockInfo()['blockedFrom']->format('d.m.Y H:i') : __('admin-users.status.forever');
                                }),

                            TD::make('blocksReceived.blockedUntil', __('admin-users.table.blocked_until'))
                                ->width('150px')
                                ->sort()
                                ->disableSearch()
                                ->render(function (User $user) {
                                    return isset($user->getBlockInfo()['blockedUntil']) ? $user->getBlockInfo()['blockedUntil']->format('d.m.Y H:i') : __('admin-users.status.forever');
                                }),

                            TD::make(__('admin-users.table.actions'))
                                ->align(TD::ALIGN_CENTER)
                                ->disableSearch()
                                ->width('100px')
                                ->cantHide()
                                ->render(function (User $user) {
                                    return user()->can($user) ? DropDown::make()
                                        ->icon('ph.regular.dots-three-outline-vertical')
                                        ->list([
                                            DropDownItem::make(__('admin-users.buttons.edit'))
                                                ->type(Color::OUTLINE_PRIMARY)
                                                ->icon('ph.regular.pencil')
                                                ->size('small')
                                                ->fullWidth()
                                                ->redirect(url('admin/users/'.$user->id.'/edit')),

                                            DropDownItem::make(__('admin-users.buttons.unblock'))
                                                ->type(Color::OUTLINE_SUCCESS)
                                                ->confirm(__('admin-users.confirms.unblock_user'), 'info')
                                                ->icon('ph.regular.shield-slash')
                                                ->size('small')
                                                ->fullWidth()
                                                ->method("unblockUser", [
                                                    "id" => $user->id,
                                                ]),

                                            DropDownItem::make(__('admin-users.buttons.delete'))
                                                ->fullWidth()
                                                ->confirm(__('admin-users.confirms.delete_user'))
                                                ->type(Color::OUTLINE_DANGER)
                                                ->icon('ph.regular.trash')
                                                ->size('small')
                                                ->method("deleteUser"),
                                        ]) : null;
                                }),
                        ])->perPage(15)->searchable(['name', 'email'])
                    ]),
            ])
                ->slug('users'),
        ];
    }



    /**
     * Удаление пользователя.
     */
    public function deleteUser()
    {
        $user = User::findByPK(intval(request()->input('id')));

        if (! user()->can('admin.users')) {
            $this->flashMessage(__('admin-users.messages.no_permission_delete'), 'error');
            return;
        }

        if (user()->getCurrentUser()?->id === $user?->id) {
            $this->flashMessage(__('admin-users.messages.cant_self_delete'), 'error');
            return;
        }

        if (! user()->can($user)) {
            $this->flashMessage(__('admin-users.messages.no_permission_delete'), 'error');
            return;
        }

        $user->delete();

        $this->flashMessage(__('admin-users.messages.delete_success'), 'success');
        $this->redirectTo('/admin/users');
    }

    /**
     * Разблокировка пользователя.
     */
    public function unblockUser()
    {
        $user = User::findByPK(intval(request()->input('id')));

        if (! user()->can($user)) {
            $this->flashMessage(__('admin-users.messages.no_permission'), 'error');
            return;
        }

        try {
            app(AdminUsersService::class)->unblockUser($user);

            $this->flashMessage(__('admin-users.messages.unblock_success'), 'success');

            $this->blockedUsers = rep(User::class)
                ->select()
                ->with('blocksReceived')
                ->where('blocksReceived.isActive', true)
                ->where(function ($qb) {
                    $qb->where('blocksReceived.blockedUntil', '>', new \DateTimeImmutable())
                        ->orWhere('blocksReceived.blockedUntil', null);
                })
                ->orderBy('id', 'desc');
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }
}
