<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Fields\Sight;
use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;

/**
 * Class Legend.
 */
abstract class Legend extends Layout
{
    /**
     * @var string
     */
    protected $template = 'admin::partials.layouts.legend';

    /**
     * Used to create the title of a group of form elements.
     *
     * @var string|null
     */
    protected $title;

    /**
     * @var Repository
     */
    protected $query;

    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target;

    /**
     * @return \Illuminate\View\View|null
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (!$this->isVisible()) {
            return;
        }

        $columns = collect($this->columns())->filter(static fn (Sight $sight) => $sight->isVisible());

        $repository = $this->target
            ? $repository->getContent($this->target)
            : $repository;

        return view($this->template, [
            'repository' => $repository,
            'columns' => $columns,
            'title' => $this->title,
        ]);
    }

    /**
     * @return Rows
     */
    public function title(?string $title = null): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return array
     */
    abstract protected function columns(): iterable;
}
