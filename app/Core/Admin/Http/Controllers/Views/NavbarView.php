<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\NavbarItem;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class NavbarView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.navigation');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function list(FluteRequest $request)
    {
        $navigation = rep(NavbarItem::class)->select()->orderBy(['parent_id' => 'asc', 'position' => 'desc'])->fetchAll();

        return view("Core/Admin/Http/Views/pages/navigation/list", [
            "navigation" => $navigation,
        ]);
    }

    public function add(FluteRequest $request)
    {
        $roles = rep(Role::class)->select();

        if (!user()->hasPermission('admin.boss'))
            $roles = $roles->where('priority', '<', user()->getHighestPriority());

        return view("Core/Admin/Http/Views/pages/navigation/add", [
            'roles' => $roles->fetchAll(),
        ]);
    }

    public function edit(FluteRequest $request, string $id)
    {
        $navigation = rep(NavbarItem::class)->select()->load('roles')->where('id', (int) $id)->fetchOne();

        if (!navbar()->hasAccess($navigation))
            return $this->error(__('def.no_access'));

        if (!$navigation)
            return $this->error(__('admin.navigation.not_found'), 404);

        $roles = rep(Role::class)->select();

        if (!user()->hasPermission('admin.boss'))
            $roles = $roles->where('priority', '<', user()->getHighestPriority());

        return view("Core/Admin/Http/Views/pages/navigation/edit", [
            "navigation" => $navigation,
            'roles' => $roles->fetchAll(),
        ]);
    }
}