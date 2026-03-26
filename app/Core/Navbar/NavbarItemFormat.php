<?php

namespace Flute\Core\Navbar;

use Flute\Core\Database\Entities\NavbarItem;
use Nette\Utils\Validators;

class NavbarItemFormat
{
    /**
     * Format a navbar item for rendering.
     *
     * @param NavbarItem $navbarItem The navbar item to format.
     * @return array The formatted navbar item.
     */
    /**
     * @param NavbarItem  $navbarItem
     * @param array|null  $childrenOverride Pre-built children array (skips ORM relation iteration).
     *                                      Pass null to iterate $navbarItem->children as before.
     */
    public function format(NavbarItem $navbarItem, ?array $childrenOverride = null): array
    {
        $result = [
            'id' => $navbarItem->id,
            'title' => $navbarItem->title,
            'description' => $navbarItem->description,
            'url' => $this->formatUrl($navbarItem->url),
            'icon' => $navbarItem->icon,
            'new_tab' => $navbarItem->new_tab,
            'children' => [],
            'roles' => [],
        ];

        if ($childrenOverride !== null) {
            $result['children'] = $childrenOverride;
        } else {
            foreach ($navbarItem->children as $child) {
                $result['children'][] = $this->format($child);
            }
        }

        foreach ($navbarItem->roles as $role) {
            $result['roles'][] = $role->name;
        }

        return $result;
    }

    /**
     * Format a URL for rendering.
     *
     * @param string $url The URL to format.
     * @return string The formatted URL.
     */
    protected function formatUrl(string $url): string
    {
        return Validators::isUrl($url) ? $url : url($url);
    }
}
