<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\User;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Table\TableColumn;

class UserBlocksView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.users');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function index(FluteRequest $request)
    {
        $table = table();
        $users = rep(User::class)->findAll();
        $usersWithBlocks = [];

        foreach ($users as $user) {
            if (!user()->canEditUser($user) || !$user->isBlocked())
                continue;

            $blockInfo = $user->getBlockInfo();
            $blockedBy = $blockInfo['blockedBy'];
            
            $usersWithBlocks[] = [
                'blocked_url' => url('admin/users/edit/'.$user->id)->get(),
                'blocked_avatar' => $user->avatar,
                'blocked_name' => $user->name,

                'admin_url' => url(user()->canEditUser($blockedBy) ? ('admin/users/edit/'.$blockedBy->id) : ('profile/'.$blockedBy->id))->get(),
                'admin_avatar' => $blockedBy->avatar,
                'admin_name' => $blockedBy->name,

                'reason' => $blockInfo['reason'],
                'blocked_until' => $blockInfo['blockedUntil'] === null ? __('admin.users.times.0') : $blockInfo['blockedUntil']->format(default_date_format()),
                
                'unblock_btn' => $this->getUnblockBtn($user->id)
            ];
        }

        $table->addColumn((new TableColumn('blocked_url', 'blocked_url'))->setVisible(false));
        $table->addCombinedColumn('blocked_avatar', 'blocked_name', __('admin.users_blocks.blocked_user'), 'blocked_url');

        $table->addColumn((new TableColumn('admin_url', 'admin_url'))->setVisible(false));
        $table->addCombinedColumn('admin_avatar', 'admin_name', __('admin.users_blocks.admin_name'), 'admin_url');

        $table->addColumn(new TableColumn('reason', __('admin.users_blocks.reason')));
        $table->addColumn(new TableColumn('blocked_until', __('admin.users_blocks.blocked_until')));

        $table->addColumn((new TableColumn('unblock_btn', ''))->setClean(false));

        $table->setData($usersWithBlocks);

        return view("Core/Admin/Http/Views/pages/users_blocks/list", [
            "table" => $table->render()
        ]);
    }

    protected function getUnblockBtn(int $blockedUserId)
    {
        $phrase = __('admin.users.unblock');
        return "<button class='unblock-user' data-unblockuser='$blockedUserId'>$phrase</button>";
    }
}