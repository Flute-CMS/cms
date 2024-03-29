<?php

namespace Flute\Core\Admin\Http\Controllers\Views\Footer;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\FooterItem;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class FooterView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.footer');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function list(FluteRequest $request)
    {
        $footer = rep(FooterItem::class)->select()->orderBy(['parent_id' => 'asc', 'position' => 'desc'])->fetchAll();

        return view("Core/Admin/Http/Views/pages/footer/list", [
            "footer" => $footer,
        ]);
    }

    public function add(FluteRequest $request)
    {
        return view("Core/Admin/Http/Views/pages/footer/add");
    }

    public function edit(FluteRequest $request, string $id)
    {
        $footer = rep(FooterItem::class)->select()->where('id', (int) $id)->fetchOne();

        if (!$footer)
            return $this->error(__('admin.footer.not_found'), 404);

        return view("Core/Admin/Http/Views/pages/footer/edit", [
            "footer" => $footer,
        ]);
    }
}