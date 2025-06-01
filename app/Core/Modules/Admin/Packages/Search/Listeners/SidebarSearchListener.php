<?php

namespace Flute\Admin\Packages\Search\Listeners;

use Flute\Admin\Packages\Search\Events\AdminSearchEvent;
use Flute\Admin\Packages\Search\Services\AdminSearchResult;

class SidebarSearchListener
{
    public static function handle(AdminSearchEvent $event)
    {
        $menuItems = app(\Flute\Admin\AdminPanel::class)->getAllMenuItems();
        $searchValue = mb_strtolower($event->getValue(), 'UTF-8');

        if (empty($searchValue)) {
            return;
        }

        self::searchInMenuItems($menuItems, $searchValue, $event);
    }

    private static function searchInMenuItems(array $menuItems, string $searchValue, AdminSearchEvent $event)
    {
        foreach ($menuItems as $item) {
            if (empty($item['url']) && empty($item['children'])) {
                continue;
            }

            if (!empty($item['title']) && !empty($item['url'])) {
                $itemTitle = mb_strtolower(__($item['title']), 'UTF-8');
                $pos = mb_strpos($itemTitle, $searchValue);

                if ($pos !== false) {
                    $relevance = 1;
                    if ($itemTitle === $searchValue) {
                        $relevance = 3;
                    } elseif ($pos === 0) {
                        $relevance = 2;
                    }

                    $searchResult = new AdminSearchResult(
                        $item['title'],
                        $item['url'],
                        $item['icon'] ?? null,
                        $item['category'] ?? null,
                        $relevance
                    );
                    $event->add($searchResult);
                }
            }

            if (!empty($item['children'])) {
                self::searchInMenuItems($item['children'], $searchValue, $event);
            }
        }
    }
}
