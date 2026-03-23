<?php

namespace Flute\Admin\Platform\Layouts;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Select;
use Flute\Admin\Platform\Action;
use Flute\Admin\Platform\Commander;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spiral\Pagination\Paginator;

/**
 * Class Table.
 */
abstract class Table extends Layout
{
    use Commander;

    /**
     * @var string
     */
    protected $template = 'admin::partials.layouts.table';

    /**
     * @var \Flute\Admin\Platform\Repository
     */
    protected $query;

    protected $searchable = false;

    protected $searchQuery = '';

    protected $searchableColumns = [];

    /**
     * Button commands.
     *
     * @var array
     */
    protected $commandBar = [];

    /**
     * Bulk actions for selected rows.
     *
     * @var array
     */
    protected $bulkActions = [];

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
     * Table title.
     *
     * The string to be displayed on top of the table.
     *
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    protected $perPage = 15;

    protected $sortColumn;

    protected $sortDirection;

    protected $compact = false;

    /**
     * Enable export functionality.
     *
     * @var bool
     */
    protected $exportable = false;

    /**
     * Export filename.
     *
     * @var string
     */
    protected $exportFilename = 'export';

    protected ?string $emptyIcon = null;

    protected ?string $emptyText = null;

    protected ?string $emptySub = null;

    protected ?Action $emptyAction = null;

    /**
     * Callback for processing each row before display.
     *
     * @var callable|null
     */
    protected $rowCallback = null;

    /**
     * Callback for processing the entire dataset before pagination.
     *
     * @var callable|null
     */
    protected $dataCallback = null;

    public function skeletonDescriptor(): array
    {
        return [
            'type' => 'table',
            'columns' => min(count($this->columns()), 6),
            'rows' => 5,
            'title' => $this->title,
            'searchable' => $this->searchable,
        ];
    }

    /**
     * @return Factory|\Illuminate\View\View|void
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (!$this->isVisible()) {
            return;
        }

        $tableId = $this->target ?? 'default';

        $allColumns = collect($this->columns());

        $columns = $allColumns->filter(static fn(TD $column) => $column->isVisible());

        // Mark columns that should be hidden by user preference
        $columns->each(static function (TD $column) use ($tableId) {
            if ($column->isHiddenByUserPreference($tableId)) {
                $column->setAttribute('hidden', true);
            }

            $column->setAttribute('tableId', $tableId);
        });

        $total = collect($this->total())->filter(static fn(TD $column) => $column->isVisible());

        $content = $repository->getContent($this->target);

        $requestSort = request()->input('sort', '');

        if (empty($requestSort)) {
            $defaultSortColumn = $columns->first(static fn(TD $column) => $column->getAttribute('defaultSort', false));
            if ($defaultSortColumn) {
                $this->sortColumn = $defaultSortColumn->getName();
                $this->sortDirection = $defaultSortColumn->getAttribute('defaultSortDirection', 'asc');
            } else {
                $this->sortColumn = '';
                $this->sortDirection = 'asc';
            }
        } else {
            $candidateColumn = ltrim($requestSort, '-');
            $allowedColumns = $columns
                ->map(static fn(TD $column) => $column->getName())
                ->filter()
                ->toArray();
            $this->sortColumn = in_array($candidateColumn, $allowedColumns, true) ? $candidateColumn : '';
            $this->sortDirection = str_starts_with($requestSort, '-') ? 'desc' : 'asc';
        }

        if ($this->isSearchable() && $this->searchQuery) {
            $content = $this->applySearch($content);
        }

        if ($this->sortColumn) {
            $content = $this->applySort($content);
        }

        $perPage = $this->perPage;
        $currentPage = $repository->has('currentPage') ? $repository->get('currentPage') : $this->getCurrentPage();

        if ($content instanceof Select || $content instanceof SelectQuery) {
            $paginator = new Paginator($perPage);
            $paginator = $paginator->withPage($currentPage);
            $paginator = $paginator->paginate($content);

            $rows = collect($content->fetchAll());

            $totalItems = $paginator->count();
            $totalPages = $paginator->countPages();
        } else {
            $collection = $this->getCollectionFromContent($content);
            $totalItems = $collection->count();
            $totalPages = (int) ceil($totalItems / $perPage);
            $rows = $collection->slice(( $currentPage - 1 ) * $perPage, $perPage)->values();
        }

        if ($this->dataCallback !== null) {
            $rows = call_user_func($this->dataCallback, $rows, $content);

            if (!$rows instanceof Collection) {
                $rows = collect($rows);
            }
        }

        if ($this->rowCallback !== null && $rows->isNotEmpty()) {
            $rows = $rows->map(fn($row) => call_user_func($this->rowCallback, $row));
        }

        return view($this->template, [
            'tableId' => $tableId,
            'repository' => $repository,
            'rows' => $rows,
            'columns' => $columns,
            'total' => $total,
            'iconNotFound' => $this->iconNotFound(),
            'textNotFound' => $this->textNotFound(),
            'subNotFound' => $this->subNotFound(),
            'buttonNotFound' => $this->buttonNotFound(),
            'searchable' => $this->isSearchable(),
            'searchValue' => $this->searchQuery,
            'compact' => $this->isCompact(),
            'bordered' => $this->bordered(),
            'hoverable' => $this->hoverable(),
            'onEachSide' => $this->onEachSide(),
            'showHeader' => $this->hasHeader($columns, collect($rows)),
            'title' => $this->title,
            'description' => $this->description,
            'commandBar' => $this->buildCommandBar($repository),
            'bulkBar' => $this->buildBulkBar($repository, $tableId),
            'paginator' => [
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
                'currentPage' => $currentPage,
                'perPage' => $perPage,
            ],
            'tableStorageKey' => 'tableSelection-' . $tableId,
            'exportable' => $this->isExportable(),
            'exportFilename' => $this->getExportFilename(),
        ]);
    }

    public function commands($commands): self
    {
        $this->commandBar = Arr::wrap($commands);

        return $this;
    }

    public function bulkActions($actions): self
    {
        $this->bulkActions = Arr::wrap($actions);

        return $this;
    }

    public function searchable(?array $columns = null): self
    {
        if ($columns) {
            $this->searchableColumns = $columns;
        }

        $this->searchQuery = request()->input('table-search', '');
        $this->searchable = true;

        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * Setting columns, by which search is allowed.
     *
     * @return $this
     */
    public function setSearchableColumns(array $columns): self
    {
        $this->searchableColumns = $columns;

        return $this;
    }

    public function perPage(?int $perPage = null): self
    {
        if ($perPage !== null) {
            $this->perPage = $perPage;
        }

        return $this;
    }

    public function title(?string $title = null): self
    {
        $this->title = $title;

        return $this;
    }

    public function description(?string $description = null): self
    {
        $this->description = $description;

        return $this;
    }

    public function compact(bool $compact = true): self
    {
        $this->compact = $compact;

        return $this;
    }

    /**
     * Enable export functionality for this table.
     *
     * @param bool $exportable Whether export is enabled
     * @param string $filename The base filename for exports
     */
    public function exportable(bool $exportable = true, string $filename = 'export'): self
    {
        $this->exportable = $exportable;
        $this->exportFilename = $filename;

        return $this;
    }

    /**
     * Get the data source target key.
     */
    public function getTarget(): string
    {
        return $this->target ?? 'default';
    }

    /**
     * Check if export is enabled.
     */
    public function isExportable(): bool
    {
        return $this->exportable;
    }

    /**
     * Get the export filename.
     */
    public function getExportFilename(): string
    {
        return $this->exportFilename;
    }

    /**
     * Get all exportable data (without pagination) for export.
     */
    public function getExportData(Repository $repository): array
    {
        $content = $repository->getContent($this->target);
        $allColumns = collect($this->columns());
        $columns = $allColumns->filter(static fn(TD $column) => $column->isVisible());

        if ($this->searchQuery) {
            $content = $this->applySearch($content);
        }

        if ($this->sortColumn) {
            $content = $this->applySort($content);
        }

        if ($content instanceof \Cycle\ORM\Select || $content instanceof \Cycle\Database\Query\SelectQuery) {
            $rows = collect($content->fetchAll());
        } else {
            $rows = $this->getCollectionFromContent($content);
        }

        if ($this->dataCallback !== null) {
            $rows = call_user_func($this->dataCallback, $rows, $content);

            if (!$rows instanceof \Illuminate\Support\Collection) {
                $rows = collect($rows);
            }
        }

        if ($this->rowCallback !== null && $rows->isNotEmpty()) {
            $rows = $rows->map(fn($row) => call_user_func($this->rowCallback, $row));
        }

        return [
            'rows' => $rows,
            'columns' => $columns,
        ];
    }

    public function prepareContent(callable $callback): self
    {
        $this->rowCallback = $callback;

        return $this;
    }

    public function dataCallback(callable $callback): self
    {
        $this->dataCallback = $callback;

        return $this;
    }

    public function empty(string $icon, string $text, string $sub = ''): self
    {
        $this->emptyIcon = $icon;
        $this->emptyText = $text;
        $this->emptySub = $sub;

        return $this;
    }

    public function emptyButton(Action $action): self
    {
        $this->emptyAction = $action;

        return $this;
    }

    protected function commandBar(): array
    {
        return $this->commandBar;
    }

    protected function buildBulkBar(Repository $repository, string $tableId): array
    {
        if (empty($this->bulkActions)) {
            return [];
        }

        return collect($this->bulkActions)->map(function ($action) use ($tableId) {
            if ($action instanceof Action) {
                $selector =
                    '#bulk-actions-'
                    . $tableId
                    . ', table#'
                    . $tableId
                    . ' .row-selector input, table#'
                    . $tableId
                    . ' .row-selector';
                $action->set('hx-include', $selector);
            }

            return $action->build($this->query);
        })->filter()->all();
    }

    protected function getCurrentPage()
    {
        return max(1, (int) request()->input('page', 1));
    }

    protected function iconNotFound(): string
    {
        return $this->emptyIcon ?? 'ph.regular.smiley-x-eyes';
    }

    protected function textNotFound(): string
    {
        return $this->emptyText ?? __('def.no_results_found');
    }

    protected function subNotFound(): string
    {
        return $this->emptySub ?? __('def.import_or_create');
    }

    protected function buttonNotFound(): ?Action
    {
        return $this->emptyAction;
    }

    /**
     * Usage for compact display of table rows.
     */
    protected function isCompact(): bool
    {
        return $this->compact;
    }

    /**
     * Usage for borders on all sides of the table and cells.
     */
    protected function bordered(): bool
    {
        return false;
    }

    /**
     * Enable a hover state on table rows.
     */
    protected function hoverable(): bool
    {
        return false;
    }

    /**
     * The number of links to display on each side of the current page link.
     */
    protected function onEachSide(): int
    {
        return 3;
    }

    /**
     * @param \Illuminate\Support\Collection|\Spiral\Pagination\Paginator $rows
     */
    protected function hasHeader(Collection $columns, $rows): bool
    {
        if ($columns->count() < 2) {
            return false;
        }

        return !empty(request()->query->all()) || $rows->isNotEmpty();
    }

    /**
     * @return array
     */
    abstract protected function columns(): iterable;

    protected function total(): array
    {
        return [];
    }

    /**
     * Applies search to the content based on its type.
     *
     * @param mixed $content
     * @return mixed
     */
    protected function applySearch($content)
    {
        if (!$this->searchQuery) {
            return $content;
        }

        $columns = $this->getSearchableColumns();

        if ($content instanceof Select || $content instanceof SelectQuery) {
            return $this->applySearchToSelect($content, $columns);
        }

        if ($content instanceof Repository) {
            $collection = collect($content->toArray());
        } elseif (is_array($content)) {
            $collection = collect($content);
        } elseif ($content instanceof Collection) {
            $collection = $content;
        } else {
            // unknown content type, return as is
            return $content;
        }

        return $this->applySearchToCollection($collection, $columns);
    }

    /**
     * Gets searchable columns.
     */
    protected function getSearchableColumns(): array
    {
        if ($this->searchableColumns) {
            return $this->searchableColumns;
        }

        return collect($this->columns())
            ->filter(static fn(TD $TD) => $TD->isSearchable())
            ->map(static fn(TD $TD) => $TD->getName())
            ->toArray();
    }

    /**
     * Applies search to a Select instance.
     */
    protected function applySearchToSelect(Select|SelectQuery $select, array $columns): Select|SelectQuery
    {
        $searchQuery = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->searchQuery);

        $select->andWhere(static function ($query) use ($columns, $searchQuery) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'LIKE', '%' . $searchQuery . '%');
            }
        });

        return $select;
    }

    /**
     * Applies search to a Collection.
     */
    protected function applySearchToCollection(Collection $collection, array $columns): Collection
    {
        $searchQuery = $this->searchQuery;

        return $collection->filter(static function ($item) use ($columns, $searchQuery) {
            foreach ($columns as $column) {
                $value = data_get($item, $column);

                if (is_scalar($value) && stripos((string) $value, $searchQuery) !== false) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Converts content to a Collection.
     *
     * @param mixed $content
     */
    protected function getCollectionFromContent($content): Collection
    {
        if (is_array($content)) {
            return collect($content);
        }

        if ($content instanceof Repository) {
            return collect($content->toArray());
        }

        if ($content instanceof Collection) {
            return $content;
        }

        // if the content type is not supported, return an empty collection
        return collect();
    }

    /**
     * Applies sorting to the content based on its type.
     *
     * @param mixed $content
     * @return mixed
     */
    protected function applySort($content)
    {
        if (!$this->sortColumn) {
            return $content;
        }

        if ($content instanceof Select || $content instanceof SelectQuery) {
            return $this->applySortToSelect($content);
        }

        return $this->applySortToCollection($this->getCollectionFromContent($content));
    }

    /**
     * Applies sorting to a Select query.
     */
    protected function applySortToSelect(Select|SelectQuery $select): Select|SelectQuery
    {
        $direction = $this->sortDirection === 'desc' ? 'DESC' : 'ASC';

        return $select->orderBy($this->sortColumn, $direction);
    }

    /**
     * Applies sorting to a Collection.
     */
    protected function applySortToCollection(Collection $collection): Collection
    {
        return $collection->sortBy($this->sortColumn, SORT_REGULAR, $this->sortDirection === 'desc');
    }
}
