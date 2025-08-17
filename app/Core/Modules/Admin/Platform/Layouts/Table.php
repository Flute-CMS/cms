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

        $columns = $allColumns->filter(function (TD $column) {
            return $column->isVisible();
        });

        // Mark columns that should be hidden by user preference
        $columns->each(function (TD $column) use ($tableId) {
            if ($column->isHiddenByUserPreference($tableId)) {
                $column->setAttribute('hidden', true);
            }

            $column->setAttribute('tableId', $tableId);
        });

        $total = collect($this->total())->filter(static fn (TD $column) => $column->isVisible());

        $content = $repository->getContent($this->target);

        $requestSort = request()->input('sort', '');

        if (empty($requestSort)) {
            $defaultSortColumn = $columns->first(fn (TD $column) => $column->getAttribute('defaultSort', false));
            if ($defaultSortColumn) {
                $this->sortColumn = $defaultSortColumn->getName();
                $this->sortDirection = $defaultSortColumn->getAttribute('defaultSortDirection', 'asc');
            } else {
                $this->sortColumn = '';
                $this->sortDirection = 'asc';
            }
        } else {
            $this->sortColumn = ltrim($requestSort, '-');
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
            $rows = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        }

        if ($this->dataCallback !== null) {
            $rows = call_user_func($this->dataCallback, $rows, $content);

            if (!$rows instanceof Collection) {
                $rows = collect($rows);
            }
        }

        if ($this->rowCallback !== null && $rows->isNotEmpty()) {
            $rows = $rows->map(function ($row) {
                return call_user_func($this->rowCallback, $row);
            });
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
            'tableStorageKey' => 'tableSelection-'.$tableId,
        ]);
    }

    protected function commandBar(): array
    {
        return $this->commandBar;
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

    protected function buildBulkBar(Repository $repository, string $tableId): array
    {
        if (empty($this->bulkActions)) {
            return [];
        }

        return collect($this->bulkActions)
            ->map(function ($action) use ($tableId) {
                if ($action instanceof Action) {
                    $selector = '#bulk-actions-' . $tableId . ', table#' . $tableId . ' .row-selector input, table#' . $tableId . ' .row-selector';
                    $action->set('hx-include', $selector);
                }

                return $action->build($this->query);
            })
            ->filter()
            ->all();
    }

    /**
     * Setting columns, by which search is allowed.
     *
     * @param array $columns
     * @return $this
     */
    public function setSearchableColumns(array $columns): self
    {
        $this->searchableColumns = $columns;

        return $this;
    }

    protected function getCurrentPage()
    {
        return max(1, (int) request()->input('page', 1));
    }

    public function perPage(int $perPage = null): self
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

    protected function iconNotFound(): string
    {
        return 'ph.regular.smiley-x-eyes';
    }

    protected function textNotFound(): string
    {
        return __('def.no_results_found');
    }

    protected function subNotFound(): string
    {
        return __('def.import_or_create');
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
     * @param \Illuminate\Support\Collection $columns
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
     *
     * @return array
     */
    protected function getSearchableColumns(): array
    {
        if ($this->searchableColumns) {
            return $this->searchableColumns;
        }

        return collect($this->columns())
            ->filter(static fn (TD $TD) => $TD->isSearchable())
            ->map(static fn (TD $TD) => $TD->getName())
            ->toArray();
    }

    /**
     * Applies search to a Select instance.
     *
     * @param Select|SelectQuery $select
     * @param array $columns
     * @return Select|SelectQuery
     */
    protected function applySearchToSelect(Select|SelectQuery $select, array $columns): Select|SelectQuery
    {
        $searchQuery = $this->searchQuery;

        $select->andWhere(function ($query) use ($columns, $searchQuery) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'LIKE', '%'.$searchQuery.'%');
            }
        });

        return $select;
    }

    /**
     * Applies search to a Collection.
     *
     * @param Collection $collection
     * @param array $columns
     * @return Collection
     */
    protected function applySearchToCollection(Collection $collection, array $columns): Collection
    {
        $searchQuery = $this->searchQuery;

        return $collection->filter(function ($item) use ($columns, $searchQuery) {
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
     * @return Collection
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
     *
     * @param Select|SelectQuery $select
     *
     * @return Select|SelectQuery
     */
    protected function applySortToSelect(Select|SelectQuery $select): Select|SelectQuery
    {
        $direction = $this->sortDirection === 'desc' ? 'DESC' : 'ASC';

        return $select->orderBy($this->sortColumn, $direction);
    }

    /**
     * Applies sorting to a Collection.
     *
     * @param Collection $collection
     * @return Collection
     */
    protected function applySortToCollection(Collection $collection): Collection
    {
        return $collection->sortBy($this->sortColumn, SORT_REGULAR, $this->sortDirection === 'desc');
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
}
