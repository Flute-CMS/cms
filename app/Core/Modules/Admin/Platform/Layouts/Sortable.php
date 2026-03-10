<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Fields\Sight;
use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;
use Illuminate\Contracts\View\Factory;

abstract class Sortable extends Layout
{
    /**
     * @var string
     */
    protected $template = 'admin::layouts.sortable';

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
     * Flag indicating whether block headers are hidden or shown.
     */
    protected bool $showBlockHeaders = false;

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
     * @var string
     */
    protected $onSortEnd;

    /**
     * @var array
     */
    protected $commands = [];

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $popover;

    /**
     * Maximum nesting depth for sortable items.
     * Default is 2 (parent + one level of children).
     */
    protected int $maxLevels = 2;

    protected ?string $emptyIcon = null;

    protected ?string $emptyText = null;

    protected ?string $emptySub = null;

    /**
     * @return Factory|\Illuminate\View\View|null
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (!$this->isVisible()) {
            return;
        }

        $columns = collect($this->columns())->filter(static fn (Sight $sight) => $sight->isVisible());

        $rows = collect()->merge($repository->getContent($this->target));

        return view($this->template, [
            'rows' => $rows,
            'columns' => $columns,
            'slug' => $repository->get('slug'),
            'title' => $this->title,
            'commands' => $this->commands,
            'description' => $this->description,
            'popover' => $this->popover,
            'onSortEnd' => $this->onSortEnd,
            'showBlockHeaders' => $this->showBlockHeaders,
            'maxLevels' => $this->maxLevels,
            'iconNotFound' => $this->iconNotFound(),
            'textNotFound' => $this->textNotFound(),
            'subNotFound' => $this->subNotFound(),
        ]);
    }

    public function popover(string $popover): self
    {
        $this->popover = $popover;

        return $this;
    }

    public function commands(array $commands): self
    {
        $this->commands = $commands;

        return $this;
    }

    public function description(?string $description = null): self
    {
        $this->description = $description;

        return $this;
    }

    public function onSortEnd(string $method): self
    {
        $this->onSortEnd = $method;

        return $this;
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
     * Show or hide block headers.
     *
     * @param bool $showHeaders Whether to show block headers or not. Default is false.
     *
     * @return $this
     */
    public function showBlockHeaders(bool $showHeaders = true): self
    {
        $this->showBlockHeaders = $showHeaders;

        return $this;
    }

    /**
     * Set maximum nesting depth for sortable items.
     *
     * @param int $levels Maximum nesting levels (1 = no nesting, 2 = one level of children, etc.)
     *
     * @return $this
     */
    public function maxLevels(int $levels): self
    {
        $this->maxLevels = max(1, $levels);

        return $this;
    }

    public function empty(string $icon, string $text, string $sub = ''): self
    {
        $this->emptyIcon = $icon;
        $this->emptyText = $text;
        $this->emptySub = $sub;

        return $this;
    }

    /**
     * @return array
     */
    abstract protected function columns(): iterable;

    protected function iconNotFound(): string
    {
        return $this->emptyIcon ?? 'ph.bold.smiley-melting-bold';
    }

    protected function textNotFound(): string
    {
        return $this->emptyText ?? __('def.no_results_found');
    }

    protected function subNotFound(): string
    {
        return $this->emptySub ?? __('def.import_or_create');
    }
}
