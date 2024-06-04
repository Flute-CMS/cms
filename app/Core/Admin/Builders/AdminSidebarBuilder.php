<?php

namespace Flute\Core\Admin\Builders;

use Flute\Core\Admin\AdminBuilder;
use Flute\Core\Admin\Contracts\AdminBuilderInterface;
use Flute\Core\Admin\Contracts\AdminSearchResultInterface;
use Flute\Core\Admin\Events\AdminSearchEvent;
use Flute\Core\Admin\Services\BladeFileParser;
use Flute\Core\Admin\Support\AdminSearchResult;

/**
 * Class AdminSidebarBuilder
 * 
 * This class is responsible for building and managing the admin sidebar items.
 */
class AdminSidebarBuilder implements AdminBuilderInterface
{
    public const COOKIE_RECENT_KEY = "__admin_recent_items";

    /**
     * @var array The sidebar items categorized by section.
     */
    protected static array $items = [
        'main' => [
            [
                'icon' => 'ph-chart-donut',
                'title' => 'admin.dashboard.title',
                'url' => '/admin/dashboard',
                'permission' => 'admin.stats'
            ],
            [
                'icon' => 'ph-gear',
                'title' => 'admin.settings.title',
                'url' => '/admin/settings',
                'permission' => 'admin.system'
            ],
            [
                'icon' => 'ph-users',
                'title' => 'admin.users_roles.title',
                'permission' => 'admin.users',
                'items' => [
                    ['title' => 'admin.users.list', 'url' => '/admin/users/list'],
                    ['title' => 'admin.users_blocks.title', 'url' => '/admin/users_blocks'],
                    ['title' => 'admin.roles.list', 'url' => '/admin/roles/list', 'permission' => 'admin.roles'],
                ]
            ],
        ],
        'resources' => [
            [
                'icon' => 'ph-cube',
                'title' => 'admin.modules.title',
                'permission' => 'admin.modules',
                'url' => '/admin/modules/list',
            ],
            [
                'icon' => 'ph-palette',
                'title' => 'admin.themes.title',
                'permission' => 'admin.templates',
                'url' => '/admin/themes/list'
            ],
            [
                'icon' => 'ph-folders',
                'title' => 'admin.composer.title',
                'permission' => 'admin.composer',
                'url' => '/admin/composer/list'
            ],
            [
                'icon' => 'ph-database',
                'title' => 'admin.databases.title',
                'permission' => 'admin.system',
                'url' => '/admin/databases/list'
            ],
            [
                'icon' => 'ph-files',
                'title' => 'admin.pages.title',
                'permission' => 'admin.pages',
                'url' => '/admin/pages/list'
            ],
        ],
        'communication' => [
            [
                'icon' => 'ph-notification',
                'title' => 'admin.notifications.title',
                'permission' => 'admin.notifications',
                'url' => '/admin/notifications/list'
            ],
            [
                'icon' => 'ph-translate',
                'title' => 'admin.translate.title',
                'permission' => 'admin.translate',
                'url' => '/admin/translate/list'
            ],
            [
                'icon' => 'ph-link-simple',
                'title' => 'admin.navbar.title',
                'permission' => 'admin.navigation',
                'url' => '/admin/navigation/list',
            ],
        ],
        'financial' => [
            [
                'icon' => 'ph-currency-circle-dollar',
                'title' => 'admin.currency.title',
                'permission' => 'admin.currency',
                'url' => '/admin/currency/list'
            ],
            [
                'icon' => 'ph-credit-card',
                'title' => 'admin.payments.title',
                'permission' => 'admin.gateways',
                'items' => [
                    ['title' => 'admin.payments.list', 'url' => '/admin/payments/list'],
                    ['title' => 'admin.payments.add', 'url' => '/admin/payments/add'],
                    ['title' => 'admin.payments.promo.list', 'url' => '/admin/payments/promo/list'],
                    ['title' => 'admin.payments.payments_header', 'url' => '/admin/payments/payments'],
                ]
            ],
        ],
        'infrastructure' => [
            [
                'icon' => 'ph-hard-drives',
                'title' => 'admin.servers.title',
                'permission' => 'admin.servers',
                'items' => [
                    ['title' => 'admin.servers.list', 'url' => '/admin/servers/list'],
                    ['title' => 'admin.servers.add', 'url' => '/admin/servers/add']
                ]
            ],
            [
                'icon' => 'ph-squares-four',
                'title' => 'admin.footer.title',
                'permission' => 'admin.footer',
                'items' => [
                    ['title' => 'admin.footer.customize', 'url' => '/admin/footer/list'],
                    ['title' => 'admin.footer.social', 'url' => '/admin/footer/socials/list'],
                ]
            ],
        ],
        'socials' => [
            [
                'icon' => 'ph-globe-simple',
                'title' => 'admin.socials.title',
                'permission' => 'admin.socials',
                'items' => [
                    ['title' => 'admin.socials.list', 'url' => '/admin/socials/list'],
                    ['title' => 'admin.socials.add', 'url' => '/admin/socials/add']
                ]
            ],
            [
                'icon' => 'ph-cloud',
                'title' => 'admin.api.title',
                'url' => '/admin/api/list',
                'permission' => 'admin.boss'
            ],
        ],
        'advanced' => [
            [
                'icon' => 'ph-test-tube',
                'title' => 'admin.event_testing.title',
                'permission' => 'admin.event_testing',
                'url' => '/admin/event_testing'
            ],
            [
                'icon' => 'ph-webhooks-logo',
                'title' => 'admin.redirects.title',
                'url' => '/admin/redirects/list',
                'permission' => 'admin.redirects'
            ],
        ],
    ];

    /**
     * Builds the admin sidebar.
     *
     * @param AdminBuilder $adminBuilder The admin builder instance.
     * @return void
     */
    public function build(AdminBuilder $adminBuilder): void
    {
        $this->addListener();
    }

    /**
     * Get the first accessible item for the user.
     *
     * @return string|null URL of the first accessible item or null if none are accessible.
     */
    public static function getFirstAccessibleItem(): ?string
    {
        foreach (self::$items as $category => $items) {
            foreach ($items as $item) {
                if (isset($item['url']) && self::hasAccess($item)) {
                    return $item['url'];
                }

                if (!self::hasAccess($item)) {
                    continue;
                }

                if (!(isset($item['items']) && is_array($item['items']))) {
                    continue;
                }

                foreach ($item['items'] as $subItem) {
                    if (self::hasAccess($subItem)) {
                        return $subItem['url'];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check if the user has access to a given item.
     *
     * @param array $item Sidebar item.
     * @return bool True if the user has access, false otherwise.
     */
    protected static function hasAccess(array $item): bool
    {
        return isset($item['permission']) ? user()->hasPermission($item['permission']) : true;
    }

    /**
     * Adds a new item to the sidebar.
     *
     * @param string $key The category key.
     * @param array $item The sidebar item.
     * @param string|null $permission Optional permission required to add the item.
     * @return void
     */
    public static function add(string $key, array $item, string $permission = null): void
    {
        if ($permission && !user()->hasPermission($permission)) {
            return;
        }

        self::$items[$key][] = $item;
    }

    /**
     * Gets items for a given category.
     *
     * @param string $key The category key.
     * @return array|null The sidebar items for the given category or null if not found.
     */
    public static function get(string $key): ?array
    {
        return self::$items[$key] ?? null;
    }

    /**
     * Gets all sidebar items.
     *
     * @return array All sidebar items.
     */
    public static function all(): array
    {
        return self::$items;
    }

    /**
     * Adds an event listener for the sidebar search.
     *
     * @return void
     */
    protected function addListener(): void
    {
        events()->addListener(AdminSearchEvent::NAME, [$this, 'searchSidebarItem']);
    }

    /**
     * Searches sidebar items based on the query from the admin search event.
     *
     * @param AdminSearchEvent $searchEvent The admin search event.
     * @return void
     */
    public function searchSidebarItem(AdminSearchEvent $searchEvent): void
    {
        $query = mb_strtolower($searchEvent->getValue());
        // $result = [];

        // foreach (self::$items as $category => $items) {
        //     foreach ($items as $item) {
        //         if (isset($item['title']) && stripos(mb_strtolower(__($item['title'])), $query) !== false && self::hasAccess($item) && isset($item['url'])) {
        //             $result[] = $this->formatSearchResult($item);
        //         }

        //         if (isset($item['items']) && is_array($item['items'])) {
        //             if (!self::hasAccess($item)) {
        //                 continue;
        //             }

        //             foreach ($item['items'] as $subItem) {
        //                 if (isset($subItem['title']) && stripos(mb_strtolower(__($subItem['title'])), $query) !== false && self::hasAccess($subItem)) {
        //                     $result[] = $this->formatSearchResult($subItem, $item['title']);
        //                 }
        //             }
        //         }
        //     }
        // }

        $parser = new BladeFileParser(BASE_PATH . 'app/Core/Admin/Http/Views/pages', $query);
        $parser->cachePhrases();

        $filesWithPhrases = $parser->searchPhrasesInCache();
        $associations = $parser->getAssociations($filesWithPhrases);

        foreach( $associations as $assoc ) {
            $searchResult = new AdminSearchResult;
            $searchResult->setTitle($assoc['phrase']);
            $searchResult->setUrl($assoc['association']['path']);
            $searchResult->setCategory($assoc['association']['association']);

            $searchEvent->add($searchResult);
        }

        // foreach ($result as $searchResult) {
        //     $searchEvent->add($searchResult);
        // }
    }

    /**
     * Formats a sidebar item into an admin search result.
     *
     * @param array $item The sidebar item.
     * @param string|null $category The category title, if any.
     * @return AdminSearchResultInterface The formatted search result.
     */
    private function formatSearchResult(array $item, ?string $category = null): AdminSearchResultInterface
    {
        $searchResult = new AdminSearchResult();
        $searchResult->setTitle(__($item['title']));
        $searchResult->setUrl($item['url']);

        if ($category) {
            $searchResult->setCategory(__($category));
        }

        if (isset($item['icon'])) {
            $searchResult->setIcon($item['icon']);
        }

        return $searchResult;
    }

    /**
     * Gets the categories that the user has access to.
     *
     * @return array The accessible categories.
     */
    public function categories(): array
    {
        $categories = self::$items;

        foreach ($categories as $key => $category) {
            $found = false;

            foreach ($category as $item) {
                if (self::hasAccess($item)) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                unset($categories[$key]);
            }
        }

        return array_keys($categories);
    }

    /**
     * Magic method to get items for a given category.
     *
     * @param string $key The category key.
     * @return array|null The sidebar items for the given category or null if not found.
     */
    public function __get(string $key): ?array
    {
        return self::$items[$key] ?? null;
    }
}