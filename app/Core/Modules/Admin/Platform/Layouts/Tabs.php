<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Class Tabs
 *
 * Represents a tabbed layout in the admin platform.
 */
abstract class Tabs extends Layout
{
    /**
     * The template used for rendering tabs.
     *
     * @var string
     */
    protected $template = 'admin::partials.layouts.tabs';

    /**
     * The collection of tabs.
     */
    protected array $tabs = [];

    /**
     * The attributes for the layout.
     *
     * @var array
     */
    protected $attributes = [
        'tabs',
        'lazyload' => true,
        'slug' => null,
        'pills' => false,
        'sticky' => true,
        'morph' => true,
    ];

    /**
     * The slug for the tabs layout.
     *
     * @var string|null
     */
    protected $slug = null;

    /**
     * Tabs constructor.
     *
     * @param array $tabs An array of tab instances.
     */
    public function __construct(array $tabs = [])
    {
        $this->tabs = $tabs;
    }

    /**
     * Sets the active tab by its slug.
     *
     * @param string $slug The slug of the tab to activate.
     * @return $this
     */
    public function activeTab(string $slug): self
    {
        $this->variables['activeTab'] = $slug;

        return $this;
    }

    public function lazyload(bool $lazyload = true): self
    {
        $this->variables['lazyload'] = $lazyload;

        return $this;
    }

    /**
     * Sets the slug for the tabs layout.
     *
     * @param string $slug The slug to assign.
     * @return $this
     */
    public function slug(string $slug): self
    {
        $this->variables['slug'] = $slug;
        $this->slug = $slug;

        return $this;
    }

    public function pills(bool $pills = true): self
    {
        $this->variables['pills'] = $pills;

        return $this;
    }

    public function sticky(bool $sticky = true): self
    {
        $this->variables['sticky'] = $sticky;

        return $this;
    }

    public function morph(bool $morph = true): self
    {
        $this->variables['morph'] = $morph;

        return $this;
    }

    /**
     * Builds the tabs layout.
     *
     * @param Repository $repository The repository instance.
     *
     * @throws InvalidArgumentException If the slug is not set.
     * @return \Illuminate\View\View
     */
    public function build(Repository $repository)
    {
        if (empty($this->variables['slug']) && ($this->variables['lazyload'] ?? true) == true) {
            throw new InvalidArgumentException('Tabs layout must have a slug.');
        }

        $this->variables['slug'] ??= $this->slug ?? uniqid();

        $tabs = [];

        foreach ($this->tabs as $tab) {
            $name = $tab->getName();
            $slug = $tab->getSlug() ?? Str::slug($name);
            $layouts = $tab->getLayouts();

            $builtLayouts = collect($layouts)
                ->map(static fn ($layout) => Arr::wrap($layout))
                ->flatMap(fn (iterable $layoutGroup, string $key) => $this->buildChild($layoutGroup, $key, $repository))
                ->all();

            $tabs[$slug] = [
                'badge' => $tab->getBadge(),
                'slug' => $slug,
                'title' => $name,
                'icon' => $tab->getIcon(),
                'active' => $this->isTabActive($slug, $tab),
                'forms' => $builtLayouts,
            ];
        }

        $this->variables['tabs'] = $tabs;
        $this->variables['activeTab'] = $this->getActiveTab();
        $this->variables['templateSlug'] = $repository->get('slug');
        $this->variables['lazyload'] ??= true;
        $this->variables['morph'] ??= true;

        return view($this->template, $this->variables);
    }

    /**
     * Determines if a tab is active.
     *
     * @param string $slug The slug of the tab.
     * @param mixed $tab The tab instance.
     */
    protected function isTabActive(string $slug, $tab): bool
    {
        $activeTab = $this->getActiveTab();
        if ($activeTab === $slug) {
            return true;
        }

        return (bool) ($activeTab === null && $tab->isActive())



        ;
    }

    /**
     * Retrieves the active tab slug.
     *
     * @return string|null The active tab slug or null if not set.
     */
    protected function getActiveTab(): ?string
    {
        if (empty($this->tabs)) {
            return null;
        }

        $layoutSlug = $this->variables['slug'] ?? $this->slug;
        $defaultTab = $this->tabs[0]->getSlug() ?? Str::slug($this->tabs[0]->getName());

        return request()->input('tab-' . $layoutSlug, $defaultTab);
    }
}
