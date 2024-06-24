<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Admin\Services\UserService;
use Flute\Core\Database\Entities\NavbarItem;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Services\NavbarService;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NavigationController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.navigation');
        $this->middleware(CSRFMiddleware::class);
    }

    public function saveOrder(FluteRequest $request)
    {
        $order = $request->input("order");

        if (!$order)
            return $this->error('Order is empty', 404);

        foreach ($order as $value) {
            $item = rep(NavbarItem::class)->select()->load('roles')->where(['id' => (int) $value['id']])->fetchOne();

            if ($item && navbar()->hasAccess($item, true)) {
                $item->position = $value['position'];

                if ($value['parent_id'] == null) {
                    $item->parent = null;
                } else {
                    $parent = rep(NavbarItem::class)->select()->load('roles')->where(['id' => (int) $value['parent_id']])->fetchOne();

                    if ($parent && navbar()->hasAccess($parent, true))
                        $item->parent = $parent;
                }

                transaction($item)->run();
            }
        }

        $this->clearCache();

        user()->log('events.navigation_order_changed');

        return $this->success();
    }

    public function edit(FluteRequest $request, string $id, UserService $userService)
    {
        $input = $request->input();

        $item = $this->getItem($id);

        if ($item instanceof JsonResponse)
            return $item;

        $item->title = $input['title'];
        $item->url = $input['url'];
        $item->new_tab = $this->b($input['new_tab']);
        $item->icon = $input['icon'];
        $item->visibleOnlyForLoggedIn = $this->b($input['visible_only_for_logged_in']);
        $item->visibleOnlyForGuests = $this->b($input['visible_only_for_guests']);

        $item->clearRoles();

        foreach ($input['roles'] as $key => $role) {
            if (!filter_var($role, FILTER_VALIDATE_BOOLEAN))
                continue;

            $role = rep(Role::class)->findByPK((int) $key);

            if ($role && (($role->priority < $userService->getCurrentUserHighestPriority(user()->getCurrentUser())) || user()->hasPermission('admin.boss'))) {
                $item->addRole($role);
            }
        }

        transaction($item)->run();

        $this->clearCache();

        user()->log('events.navigation_edit', $item->id);

        return $this->success();
    }

    public function add(FluteRequest $request, UserService $userService)
    {
        $input = $request->input();

        $item = new NavbarItem;

        $item->title = $input['title'];
        $item->url = $input['url'];
        $item->new_tab = $this->b($input['new_tab']);
        $item->icon = $input['icon'];
        $item->visibleOnlyForLoggedIn = $this->b($input['visible_only_for_logged_in']);
        $item->visibleOnlyForGuests = $this->b($input['visible_only_for_guests']);

        foreach ($input['roles'] as $key => $role) {
            if (!filter_var($role, FILTER_VALIDATE_BOOLEAN))
                continue;

            $role = rep(Role::class)->findByPK((int) $key);

            if ($role && (($role->priority < $userService->getCurrentUserHighestPriority(user()->getCurrentUser())) || user()->hasPermission('admin.boss'))) {
                $item->addRole($role);
            }
        }

        transaction($item)->run();

        $this->clearCache();

        user()->log('events.navigation_added', $input['title']);

        return $this->success();
    }

    public function delete(FluteRequest $request, string $id)
    {
        $item = $this->getItem($id);

        if ($item instanceof JsonResponse)
            return $item;

        transaction($item, 'delete')->run();

        $this->clearCache();

        user()->log('events.navigation_deleted', $id);

        return $this->success();
    }

    /**
     * @return NavbarItem|JsonResponse
     */
    protected function getItem(string $id)
    {
        $navigation = rep(NavbarItem::class)->select()->load('roles')->where('id', (int) $id)->fetchOne();

        if (!$navigation)
            return $this->error(__('admin.navigation.not_found'), 404);

        if (!navbar()->hasAccess($navigation, true))
            return $this->error(__('def.no_access'));

        return $navigation;
    }

    protected function clearCache() : void
    {
        cache()->delete(NavbarService::CACHE_KEY);
    }

    protected function b(string $str): bool
    {
        return filter_var($str, FILTER_VALIDATE_BOOLEAN);
    }
}