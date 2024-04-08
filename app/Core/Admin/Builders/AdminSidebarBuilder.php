<?php

namespace Flute\Core\Admin\Builders;

use Flute\Core\Admin\AdminBuilder;
use Flute\Core\Admin\Contracts\AdminBuilderInterface;
use Flute\Core\Admin\Exceptions\UnknownSidebarItemException;

/**
 * Все сводится к тому, что это простой сборщик пунктов...
 */

class AdminSidebarBuilder implements AdminBuilderInterface
{
    public const COOKIE_RECENT_KEY = "__admin_recent_items";

    protected static array $items = [
        'main' => [
            [
                'icon' => 'ph-chart-donut',
                'title' => 'admin.dashboard',
                'url' => '/admin/',
                // 'tag' => 'Бета',
                'permission' => 'admin.stats'
            ],
            [
                'icon' => 'ph-gear',
                'title' => 'admin.settings',
                'url' => '/admin/settings',
                'permission' => 'admin.system'
            ],
            [
                'icon' => 'ph-cloud',
                'title' => 'API',
                'url' => '/admin/api/list',
                'permission' => 'admin.boss'
            ],
        ],
        'additional' => [
            [
                'icon' => 'ph-cube',
                'title' => 'admin.modules.title',
                'permission' => 'admin.modules',
                'url' => '/admin/modules/list',
                // 'items' => [
                //     ['title' => 'admin.modules.list', 'url' => '/admin/modules/list'],
                // ['title' => 'admin.modules.install', 'url' => '/admin/modules/install'],
                // ['title' => 'admin.modules.catalog', 'url' => '/admin/modules/catalog']
                // ]
            ],
            [
                'icon' => 'ph-palette',
                'title' => 'admin.themes.title',
                'permission' => 'admin.templates',
                'url' => '/admin/themes/list'
                // 'items' => [
                // ['title' => 'admin.themes.list', 'url' => '/admin/themes/list'],
                // ['title' => 'admin.themes.install', 'url' => '/admin/themes/install'],
                // ['title' => 'admin.themes.catalog', 'url' => '/admin/themes/catalog']
                // ]
            ],
            [
                'icon' => 'ph-files',
                'title' => 'admin.pages.title',
                'permission' => 'admin.pages',
                'url' => '/admin/pages/list'
            ],
            [
                'icon' => 'ph-folders',
                'title' => 'admin.composer.title',
                'permission' => 'admin.composer',
                'url' => '/admin/composer/list'
            ],
            [
                'icon' => 'ph-translate',
                'title' => 'admin.translate.title',
                'permission' => 'admin.translate',
                'url' => '/admin/translate/list'
            ],
            [
                'icon' => 'ph-currency-circle-dollar',
                'title' => 'admin.currency.title',
                'permission' => 'admin.currency',
                'url' => '/admin/currency/list'
            ],
            [
                'icon' => 'ph-database',
                'title' => 'admin.databases.title',
                'permission' => 'admin.system',
                'url' => '/admin/databases/list'
            ],
            [
                'icon' => 'ph-notification',
                'title' => 'admin.notifications.title',
                'permission' => 'admin.notifications',
                'url' => '/admin/notifications/list'
            ],
            [
                'icon' => 'ph-link-simple',
                'title' => 'admin.navbar.title',
                'permission' => 'admin.navigation',
                'url' => '/admin/navigation/list',
                // 'items' => [
                //     ['title' => 'admin.navbar.customize', 'url' => '/admin/navigation/list']
                // ]
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
                'icon' => 'ph-globe-simple',
                'title' => 'admin.socials.title',
                'permission' => 'admin.socials',
                'items' => [
                    ['title' => 'admin.socials.list', 'url' => '/admin/socials/list'],
                    ['title' => 'admin.socials.add', 'url' => '/admin/socials/add']
                ]
            ],
            [
                'icon' => 'ph-users',
                'title' => 'admin.users_roles.title',
                'permission' => 'admin.users',
                'items' => [
                    ['title' => 'admin.users.list', 'url' => '/admin/users/list'],
                    ['title' => 'admin.roles.list', 'url' => '/admin/roles/list', 'permission' => 'admin.roles']
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
        'recent' => [],
    ];

    /**
     * Get the first accessible item for the user.
     *
     * @return string|null URL of the first accessible item or null if none are accessible
     */
    public static function getFirstAccessibleItem(): ?string
    {
        foreach (self::$items as $category => $items) {
            foreach ($items as $item) {
                // Проверка основных элементов
                if (isset($item['url']) && self::hasAccess($item)) {
                    return $item['url'];
                }

                // Проверка подпунктов, если они есть
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
     * @param array $item Sidebar item
     * @return bool
     */
    protected static function hasAccess(array $item): bool
    {
        return isset($item['permission']) ? user()->hasPermission($item['permission']) : true;
    }

    public function build(AdminBuilder $adminBuilder): void
    {
        self::initRecentItems();
    }

    public static function add(string $key, array $item, string $permission = null)
    {
        if ($permission && !user()->hasPermission($permission)) {
            return;
        }

        if (!isset(self::$items[$key]))
            throw new UnknownSidebarItemException($key);

        self::$items[$key][] = $item;
    }

    public static function get(string $key)
    {
        if (!isset(self::$items[$key]))
            throw new UnknownSidebarItemException($key);

        return self::$items[$key];
    }

    public static function all(): array
    {
        return self::$items;
    }

    public static function initRecentItems(): void
    {
        $items = json_decode(cookie(self::COOKIE_RECENT_KEY) ?? '[]', true) ?? [];

        foreach ($items as $title => $path) {
            if (self::isValidItem($title, $path)) {
                self::$items['recent'][$title] = $path;
            }
        }
    }

    protected static function isValidItem($title, $path): bool
    {
        // Проверка существования элемента в основных категориях
        foreach (['main', 'additional'] as $category) {
            foreach (self::$items[$category] as $item) {
                if ($category === 'additional' && isset($item['items'])) {
                    // Проверка для подпунктов в 'additional'
                    foreach ($item['items'] as $subItem) {
                        if (isset($subItem['title']) && $subItem['title'] === $title && isset($subItem['url']) && $subItem['url'] === $path) {
                            return true;
                        }
                    }
                } else {
                    // Проверка для пунктов в 'main' и 'additional' без подпунктов
                    if (isset($item['title']) && $item['title'] === $title && isset($item['url']) && $item['url'] === $path) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function removeRecentItem(string $title): array
    {
        self::initRecentItems();

        if (isset(self::$items['recent'][$title])) {
            unset(self::$items['recent'][$title]);
        }

        return self::$items['recent'];
    }

    public function __get(string $key)
    {
        return self::$items[$key] ?? null;
    }
}