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
    public function format(NavbarItem $navbarItem): array
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

        foreach ($navbarItem->children as $child) {
            $result['children'][] = $this->format($child);
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
