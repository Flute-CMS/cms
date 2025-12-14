<?php

namespace Flute\Admin\Platform;

use Flute\Admin\Platform\Contracts\LayoutInterface;
use Flute\Admin\Platform\Traits\IsVisible;
use Illuminate\Support\Arr;

abstract class Layout implements LayoutInterface
{
    use IsVisible;

    /**
     * The Main template to display the layer
     * Represents the view() argument.
     *
     * @var string
     */
    protected $template;

    /**
     * Nested layers that should be
     * displayed along with it.
     *
     * @var Layout[]
     */
    protected $layouts = [];

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * @var Repository
     */
    protected $query;

    /**
     * @return mixed
     */
    abstract public function build(Repository $repository);

    public function jsonSerialize(): array
    {
        $props = collect(get_object_vars($this));

        return $props->except(['query'])->toArray();
    }

    /**
     * @return mixed
     */
    protected function buildAsDeep(Repository $repository)
    {
        if (!$this->query) {
            $this->query = $repository;
        }

        if (!$this->isVisible()) {
            return;
        }

        $build = collect($this->layouts)
            ->map(static fn ($layouts) => Arr::wrap($layouts))
            ->map(fn (iterable $layouts, string $key) => $this->buildChild($layouts, $key, $repository))
            ->collapse()
            ->all();

        $variables = array_merge($this->variables, [
            'templateSlug' => $repository->get('slug'),
            'manyForms' => $build,
        ]);

        return view($this->template, $variables);
    }

    /**
     * @param array      $layouts
     * @param int|string $key
     *
     * @return array
     */
    protected function buildChild(iterable $layouts, $key, Repository $repository)
    {
        return collect($layouts)
            ->flatten()
            ->map(static fn ($layout) => is_object($layout) ? $layout : app()->get($layout))
            ->filter(fn () => $this->isVisible())
            ->reduce(static function ($build, self $layout) use ($key, $repository) {
                $build[$key][] = $layout->build($repository);

                return $build;
            }, []);
    }
}
