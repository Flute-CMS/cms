<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Admin\Services\UserService;
use Flute\Core\Database\Entities\Permission;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class RolesController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.roles');
        $this->middleware(HasPermissionMiddleware::class);
        $this->middleware(CSRFMiddleware::class);
    }

    public function saveOrder(FluteRequest $request, UserService $userService)
    {
        $userPriority = $userService->getCurrentUserHighestPriority(user()->getCurrentUser());
        $orderedRoles = $request->input('order');

        foreach ($orderedRoles as $roleWithPriority) {
            $role = rep(Role::class)->findByPK($roleWithPriority['id']);

            if (($role && $role->priority < $userPriority) || user()->hasPermission('admin.boss')) {
                $role->priority = $roleWithPriority['priority'];
                transaction($role)->run();
            } else {
                return $this->error('Недостаточно прав для изменения приоритета этой роли.');
            }
        }

        user()->log('events.roles_order_changed');

        return $this->success();
    }

    public function delete(FluteRequest $request, string $roleId, UserService $userService)
    {
        $role = rep(Role::class)->findByPK($roleId);
        if (!$role) {
            return $this->error(__('admin.roles.not_found'));
        }

        $myPriority = $userService->getHighestPriorityRole(user()->getCurrentUser());

        if ($myPriority->priority <= $role->priority && !user()->hasPermission('admin.boss'))
            return $this->error(__('admin.roles.no_access'));

        transaction($role, 'delete')->run();

        user()->log('events.role_deleted', $roleId);

        return $this->success();
    }

    public function add(FluteRequest $request, UserService $userService)
    {
        $role = new Role();
        $role->name = $request->input('name');
        $role->color = $request->input('color');

        $highestPriority = rep(Role::class)->select()->orderBy('priority', 'desc')->fetchOne();
        $highestPriority->priority = $highestPriority->priority + 1;
        transaction($highestPriority)->run();

        $role->priority = 1;

        transaction($role)->run();

        $this->syncPermissions($role, $request->input('permissions', []));

        user()->log('events.role_added', $request->name);

        return $this->success();
    }

    public function edit(FluteRequest $request, string $roleId, UserService $userService)
    {
        $role = rep(Role::class)->findByPK($roleId);
        if (!$role) {
            return $this->error(__('admin.roles.not_found'));
        }

        $myPriority = $userService->getHighestPriorityRole(user()->getCurrentUser());

        if ($myPriority->priority <= $role->priority && !user()->hasPermission('admin.boss'))
            return $this->error(__('admin.roles.no_access'));

        $role->name = $request->input('name', $role->name);
        $role->color = $request->input('color', $role->color);

        transaction($role)->run();

        user()->log('events.role_edited', $roleId);

        $this->syncPermissions($role, $request->input('permissions', []));

        return $this->success();
    }

    private function syncPermissions(Role $role, array $permissions)
    {
        foreach ($permissions as $permissionId => $status) {
            $permission = rep(Permission::class)->findByPK($permissionId);

            $status = filter_var($status, FILTER_VALIDATE_BOOLEAN);

            if ($permission) {

                // просто снимаем то, что у нас не по правилам. Пусть ломают голову дебилы
                if (!user()->hasPermission($permission->name) && !user()->hasPermission('admin.boss')) {
                    $role->removePermission($permission);
                    continue;
                }

                if ($status && !$role->hasPermission($permission)) {
                    $role->addPermission($permission);
                } elseif (!$status) {
                    $role->removePermission($permission);
                }
            }
        }

        transaction($role)->run();
    }
}