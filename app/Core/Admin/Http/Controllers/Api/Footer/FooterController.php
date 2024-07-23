<?php

namespace Flute\Core\Admin\Http\Controllers\Api\Footer;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\FooterItem;
use Flute\Core\Services\FooterService;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\JsonResponse;

class FooterController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.footer');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function saveOrder(FluteRequest $request)
    {
        $order = $request->input("order");

        if (!$order)
            return $this->error('Order is empty', 404);

        foreach ($order as $value) {
            $item = rep(FooterItem::class)->select()->where(['id' => (int) $value['id']])->fetchOne();

            if ($item) {
                $item->position = $value['position'];

                if (!isset($value['parentId'])) {
                    $item->parent = null;
                } else {
                    $parent = rep(FooterItem::class)->select()->where(['id' => (int) $value['parentId']])->fetchOne();

                    if ($parent)
                        $item->parent = $parent;
                }

                transaction($item)->run();
            }
        }

        $this->clearCache();

        return $this->success();
    }

    public function edit(FluteRequest $request, string $id)
    {
        $input = $request->input();

        $item = $this->getItem($id);

        if ($item instanceof JsonResponse)
            return $item;

        $item->title = $input['title'];
        $item->url = $input['url'];
        $item->new_tab = $this->b($input['new_tab']);

        transaction($item)->run();

        $this->clearCache();

        return $this->success();
    }

    public function add(FluteRequest $request)
    {
        $input = $request->input();

        $item = new FooterItem;

        $item->title = $input['title'];
        $item->url = $input['url'];
        $item->new_tab = $this->b($input['new_tab']);

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

    protected function getItem(string $id)
    {
        $footer = rep(FooterItem::class)->select()->where('id', (int) $id)->fetchOne();

        if (!$footer)
            return $this->error(__('admin.footer.not_found'), 404);

        return $footer;
    }

    protected function clearCache() : void
    {
        cache()->delete(FooterService::CACHE_KEY);
    }

    protected function b(string $str): bool
    {
        return filter_var($str, FILTER_VALIDATE_BOOLEAN);
    }
}