<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\Page;
use Flute\Core\Database\Entities\Permission;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class PagesView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.pages');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function list(FluteRequest $request)
    {
        $pages = rep(Page::class)->select()->orderBy('id', 'desc')->fetchAll();

        $table = table()->fromEntity(
            $pages,
            ['keywords', 'robots', 'og_title', 'og_description', 'og_image', 'blocks', 'permissions']
        )->withActions('pages');

        return view("Core/Admin/Http/Views/pages/pages/list", [
            "table" => $table->render(),
        ]);
    }

    public function add(FluteRequest $request)
    {
        return view("Core/Admin/Http/Views/pages/pages/add", [
            "permissions" => $this->getPermissions()
        ]);
    }

    public function edit(FluteRequest $request, string $id)
    {
        $page = rep(Page::class)
            ->select()
            ->load(['blocks', 'permissions'])
            ->where('id', $id)
            ->fetchOne();

        if (!$page)
            return $this->error(__('admin.pages.not_found'), 404);

        $blocks = [];

        foreach( $page->blocks as $block ) {
            $blocks[] = json_decode($block->json, true);
        }

        return view("Core/Admin/Http/Views/pages/pages/edit", [
            "page" => $page,
            "permissions" => $this->getPermissions(),
            "blocks" => json_encode($blocks)
        ]);
    }

    protected function getPermissions()
    {
        return rep(Permission::class)->findAll();
    }
}