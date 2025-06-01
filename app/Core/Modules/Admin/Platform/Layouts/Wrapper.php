<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;
use Illuminate\Support\Arr;

/**
 * Class Wrapper.
 */
abstract class Wrapper extends Layout
{
    /**
     * Wrapper constructor.
     *
     * @param Layout[] $layouts
     */
    public function __construct(string $template, array $layouts = [])
    {
        $this->template = $template;
        $this->layouts = $layouts;
    }

    /**
     * @return \Illuminate\Contracts\View\View|void
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (!$this->isVisible()) {
            return;
        }

        $build = collect($this->layouts)
            ->map(function ($layout, $key) use ($repository) {
                $items = $this->buildChild(Arr::wrap($layout), $key, $repository);

                return !is_array($layout) ? reset($items)[0] : reset($items);
            })
            ->merge($repository->all())
            ->all();

        return view($this->template, $build);
    }
}
