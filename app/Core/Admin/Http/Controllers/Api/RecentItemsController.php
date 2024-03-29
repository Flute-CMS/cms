<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Builders\AdminSidebarBuilder;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Nette\Utils\Json;

class RecentItemsController extends AbstractController
{
    public function add(FluteRequest $request)
    {
        $title = $request->input('title');
        $path = $request->input('url');
        $currentItems = json_decode(cookie(AdminSidebarBuilder::COOKIE_RECENT_KEY), true) ?? [];

        if (isset($currentItems[$title])) {
            unset($currentItems[$title]);
        }

        $currentItems = [$title => $path] + $currentItems;

        $currentItems = array_slice($currentItems, 0, 10);

        cookie()->set(AdminSidebarBuilder::COOKIE_RECENT_KEY, json_encode($currentItems));

        return $this->success();
    }

    public function remove(FluteRequest $request)
    {
        $title = $request->input('title');

        // Удаление элемента из списка недавних элементов
        $newItems = AdminSidebarBuilder::removeRecentItem($title);

        cookie()->set(AdminSidebarBuilder::COOKIE_RECENT_KEY, Json::encode($newItems));

        return $this->success();
    }
}