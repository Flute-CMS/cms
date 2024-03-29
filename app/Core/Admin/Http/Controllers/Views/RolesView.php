<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Admin\Services\UserService;
use Flute\Core\Database\Entities\Permission;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class RolesView extends AbstractController
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        HasPermissionMiddleware::permission('admin.roles');
        $this->middleware(HasPermissionMiddleware::class);

        $this->userService = $userService;
    }

    public function list(FluteRequest $request)
    {
        $roles = rep(Role::class)->select()->orderBy('priority', 'desc')->fetchAll();

        return view("Core/Admin/Http/Views/pages/roles/list", [
            "roles" => $roles,
            "priority" => $this->userService->getHighestPriorityRole(user()->getCurrentUser()),
        ]);
    }

    public function add(FluteRequest $request)
    {
        return view("Core/Admin/Http/Views/pages/roles/add", [
            "permissions" => rep(Permission::class)->findAll()
        ]);
    }

    public function edit(FluteRequest $request, string $id)
    {
        $role = rep(Role::class)->select()->load('permissions')->fetchOne([
            'id' => (int) $id
        ]);

        if( !$role )
            return $this->error(__('admin.roles.not_found'), 404);

        $myPriority = $this->userService->getHighestPriorityRole(user()->getCurrentUser());

        if( $myPriority->priority <= $role->priority && !user()->hasPermission('admin.boss') )
            return $this->error(__('admin.roles.no_access'));

        return view("Core/Admin/Http/Views/pages/roles/edit", [
            "role" => $role,
            "permissions" => rep(Permission::class)->findAll()
        ]);
    }
}