<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\Page;
use Flute\Core\Database\Entities\PageBlock;
use Flute\Core\Database\Entities\Permission;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class PagesController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.pages');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function add(FluteRequest $request)
    {
        $page = new Page;
        $page->route = $this->fixRoute($request->route);
        $page->title = $request->title;
        $page->description = $request->description;
        $page->keywords = $request->keywords;
        $page->robots = $request->robots;
        $page->og_title = $request->og_title;
        $page->og_description = $request->og_description;
        $page->og_image = $request->og_image;

        $this->syncPermissions($page, $request->input('permissions', []));

        $blocks = json_decode($request->input('blocks', '[]'), true);

        foreach ($blocks as $block) {
            $pageBlock = new PageBlock;
            $pageBlock->json = \Nette\Utils\Json::encode($block);

            $page->addBlock($pageBlock);
        }

        user()->log('events.custom_pages_added', $request->title);

        transaction($page)->run();

        return $this->success();
    }

    public function delete(FluteRequest $request, string $id)
    {
        $page = $this->getPage((int) $id);

        if (!$page)
            return $this->error(__('admin.pages.not_found'), 404);

        user()->log('events.custom_pages_deleted', $id);

        transaction($page, 'delete')->run();

        return $this->success();
    }

    public function edit(FluteRequest $request, string $id)
    {
        $page = $this->getPage((int) $id);

        if (!$page)
            return $this->error(__('admin.pages.not_found'), 404);

        $page->route = $this->fixRoute($request->route);
        $page->title = $request->title;
        $page->description = $request->description;
        $page->keywords = $request->keywords;
        $page->robots = $request->robots;
        $page->og_title = $request->og_title;
        $page->og_description = $request->og_description;
        $page->og_image = $request->og_image;

        $this->syncPermissions($page, $request->input('permissions', []));

        $page->removeAllBlocks();

        $blocks = json_decode($request->input('blocks', '[]'), true);

        foreach ($blocks as $block) {
            $pageBlock = new PageBlock;
            $pageBlock->json = \Nette\Utils\Json::encode($block);

            $page->addBlock($pageBlock);
        }

        user()->log('events.custom_page_edited', $id);

        transaction($page)->run();

        return $this->success();
    }

    public function checkRoute(FluteRequest $request)
    {
        $route = $request->route;

        $page = rep(Page::class)
            ->select()
            ->where([
                'route' => $route
            ])
            ->orWhere([
                'route' => "/$route",
            ])
            ->andWhere('id', '!=', $request->id)
            ->fetchOne();

        if ($page)
            return $this->error(__('admin.pages.route_exists'), 400);

        if (!preg_match('/^[a-zA-Z0-9\-\/]+$/', $route)) {
            return $this->error(__('admin.pages.validate_route_error'), 400);
        }

        return response()->json([
            'message' => 'Valid',
        ]);
    }

    protected function fixRoute(string $route)
    {
        return $route[0] === '/' ? $route : "/$route";
    }

    private function syncPermissions(Page $page, array $permissions)
    {
        foreach ($permissions as $permissionId => $status) {
            $permission = rep(Permission::class)->findByPK($permissionId);

            $status = filter_var($status, FILTER_VALIDATE_BOOLEAN);

            if ($permission) {

                // просто снимаем то, что у нас не по правилам. Пусть ломают голову дебилы
                if (!user()->hasPermission($permission->name) && !user()->hasPermission('admin.boss')) {
                    $page->removePermission($permission);
                    continue;
                }

                if ($status && !$page->hasPermission($permission)) {
                    $page->addPermission($permission);
                } elseif (!$status) {
                    $page->removePermission($permission);
                }
            }
        }
    }

    protected function getPage(int $id)
    {
        return rep(Page::class)->findByPK($id);
    }
}