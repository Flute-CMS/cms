<?php

namespace Flute\Admin\Platform\Fields;

use Flute\Admin\Platform\Field;
use Flute\Admin\Platform\Layout;

/**
 * Class Tab
 */
class Tab extends Field
{
    /**
     * Set the title of the tab.
     *
     * @param string $title
     * @return self
     */
    public function title(string $title): self
    {
        $this->set('title', $title);
        return $this;
    }

    /**
     * Set the badge of the tab.
     *
     * @param mixed $badge
     * @return self
     */
    public function badge($badge): self
    {
        $this->set('badge', $badge);
        return $this;
    }

    /**
     * Set the icon of the tab.
     *
     * @param string $icon
     * @return self
     */
    public function icon(string $icon): self
    {
        $this->set('icon', $icon);
        return $this;
    }

    /**
     * Set the active tab.
     *
     * @param bool $active
     * @return self
     */
    public function active(bool $active = true): self
    {
        $this->set('active', $active);
        return $this;
    }

    /**
     * Set the nested layouts.
     *
     * @param Layout[] $layouts
     * @return self
     */
    public function layouts(array $layouts): self
    {
        $this->set('layouts', $layouts);
        return $this;
    }

    /**
     * Set the slug for the tab.
     *
     * @param string|int $slug
     * @return self
     */
    public function slug($slug): self
    {
        $this->set('slug', $slug);
        return $this;
    }

    /**
     * Set the href for the tab.
     *
     * @param string $href
     * @return self
     */
    public function href(string $href): self
    {
        $this->set('href', $href);
        return $this;
    }

    /**
     * Get the title of the tab.
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->get('title');
    }

    /**
     * Get the badge of the tab.
     *
     * @return mixed
     */
    public function getBadge()
    {
        return $this->get('badge');
    }

    /**
     * Get the icon of the tab.
     *
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->get('icon');
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    /**
     * Check if the tab is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->get('active', false);
    }

    /**
     * Get the nested layouts.
     *
     * @return Layout[]
     */
    public function getLayouts(): array
    {
        return $this->get('layouts', []);
    }

    /**
     * Get the slug for the tab.
     *
     * @return string|int|null
     */
    public function getSlug()
    {
        return $this->get('slug');
    }

    /**
     * Get the href for the tab.
     *
     * @return string|null
     */
    public function getHref(): ?string
    {
        return $this->get('href');
    }
}
