<?php

namespace Flute\Admin\Platform;

use Flute\Admin\Platform\Fields\Cell;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

class Sight extends Cell
{
    /**
     * Builds a column heading.
     *
     * @return Factory|View
     */
    public function buildDt()
    {
        return view('admin::partials.layouts.dt', [
            'column' => $this->column,
            'title' => $this->title,
            'popover' => $this->popover,
        ]);
    }

    /**
     * Builds content for the column.
     *
     * @param Repository $repository
     *
     * @return string|\Illuminate\Contracts\Support\Htmlable|null
     */
    public function buildDd($repository)
    {
        $value = $this->render
            ? $this->handler($repository)
            : $repository->getContent($this->name);

        return $this->render === null
            ? e($value)
            : $value;
    }
}
