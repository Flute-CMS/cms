<?php

namespace Flute\Core\Admin\Http\Controllers\Api\Footer;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\FooterSocial;
use Flute\Core\Services\FooterSocialService;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class FooterSocialsController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.footer');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function edit(FluteRequest $request, string $id)
    {
        $input = $request->input();

        $item = $this->getItem($id);

        if ($item instanceof JsonResponse)
            return $item;

        $item->name = $input['name'];
        $item->url = $input['url'];
        $item->icon = $input['icon'];

        transaction($item)->run();

        $this->clearCache();

        return $this->success();
    }

    public function add(FluteRequest $request)
    {
        $input = $request->input();

        $item = new FooterSocial;

        $item->name = $input['name'];
        $item->url = $input['url'];
        $item->icon = $input['icon'];

        transaction($item)->run();

        $this->clearCache();

        return $this->success();
    }

    public function delete(FluteRequest $request, string $id)
    {
        $item = $this->getItem($id);

        if ($item instanceof JsonResponse)
            return $item;

        transaction($item, 'delete')->run();

        $this->clearCache();

        return $this->success();
    }

    protected function clearCache() : void
    {
        cache()->delete(FooterSocialService::CACHE_KEY);
    }

    protected function getItem(string $id)
    {
        $footer = rep(FooterSocial::class)->select()->where('id', (int) $id)->fetchOne();

        if (!$footer)
            return $this->error(__('admin.footer.not_found'), 404);

        return $footer;
    }
}