<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\Redirect;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class RedirectsView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.redirects');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function list(): Response
    {
        $table = table()->setSelectable(true);
        $table->setPhrases([
            'fromUrl' => __('admin.redirects.from'),
            'toUrl' => __('admin.redirects.to'),
        ]);

        $table->fromEntity(rep(Redirect::class)->findAll(), ['conditionGroups'])->withActions('redirects');

        return view("Core/Admin/Http/Views/pages/redirects/list", [
            "redirects" => $table->render()
        ]);
    }

    public function add(FluteRequest $request): Response
    {
        return view("Core/Admin/Http/Views/pages/redirects/add");
    }

    public function update(FluteRequest $request, string $id): Response
    {
        $redirect = $this->getRedirect((int) $id);

        if (!$redirect)
            return $this->error(__('def.not_found'), 404);

        return view("Core/Admin/Http/Views/pages/redirects/edit", [
            'redirect' => $redirect
        ]);
    }

    protected function getRedirect(int $id)
    {
        return rep(Redirect::class)->select()->where('id', $id)->load('conditionGroups')->load('conditionGroups.conditions')->fetchOne();
    }
}