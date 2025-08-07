<?php

namespace Flute\Core\Components;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Select;
use Exception;
use Flute\Core\Support\FluteComponent;
use Flute\Core\Support\Htmx\Response\HtmxResponse;

/**
 * Abstract Table Component for displaying data in a tabular format.
 * Designed to be reusable across different modules.
 */
abstract class Table extends FluteComponent
{
    protected ?string $entityClass = null;

    /**
     * Columns to display in the table.
     *
     * Each column can have the following keys:
     * - label: The header label of the column.
     * - field: The data field name.
     * - allowSort: (bool) Whether the column is sortable.
     * - searchable: (bool) Whether the column is searchable.
     * - searchFields: (array) List of database fields to search in for this column.
     * - searchTransform: (callable|null) Function to transform search value before querying.
     * - renderer: (callable|null) Optional callback for custom rendering.
     * - defaultSort: (bool) Whether this column is the default sort field.
     * - defaultDirection: ('asc'|'desc') Default sort direction for this column.
     * - width: (string) CSS width value for the column (e.g. '100px', '20%').
     * - class: (string) Additional CSS classes for the column.
     * - visible: (bool) Whether the column is visible by default.
     * - tooltip: (string) Optional tooltip text for the column header.
     * - formatValue: (callable|null) Optional callback for formatting raw values.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $columns = [];

    /**
     * The current search query.
     *
     * @var string|null
     */
    public ?string $search = null;

    /**
     * The field currently used for sorting.
     *
     * @var string|null
     */
    public ?string $sortField = null;

    /**
     * The sort direction ('asc' or 'desc').
     *
     * @var string
     */
    public string $sortDirection = 'asc';

    /**
     * The number of records per page.
     *
     * @var int
     */
    public int $perPage = 10;

    /**
     * Options for records per page.
     *
     * @var array<int>
     */
    public array $paginationOptions = [10, 25, 50, 100];

    /**
     * The current page number.
     *
     * @var int
     */
    public int $page = 1;

    /**
     * Additional filters to apply.
     *
     * @var array<string, mixed>
     */
    protected array $additionalFilters = [];

    /**
     * The Cycle ORM Select instance.
     *
     * @var SelectQuery|\Cycle\ORM\Select|null
     */
    protected $select = null;

    /**
     * The array data source.
     *
     * @var array<int, mixed>|null
     */
    protected ?array $data = null;

    /**
     * Optional callable that transforms each row before rendering.
     * Signature: function (mixed $row): array|object
     *
     * @var callable|null
     */
    protected $rowTransformer = null;

    /**
     * Define properties to be preserved between requests.
     *
     * @var array<int, string>
     */
    protected $props = [
        'sortField',
        'sortDirection',
        'page',
    ];

    /**
     * Initialize component settings.
     *
     * @return void
     * @throws Exception
     */
    public function mount()
    {
        if (empty($this->columns())) {
            throw new Exception('Table component requires at least one column definition.');
        }

        if ($this->entityClass && is_null($this->select)) {
            $this->select = rep($this->entityClass)->select();
        }

        $this->page = max(1, (int) request()->input('page', 1));
        $this->search = request()->input('search', $this->search);

        if (is_null($this->sortField) && !empty($this->columns())) {
            $defaultSortColumn = array_filter($this->columns(), function ($column) {
                return isset($column['defaultSort']) && $column['defaultSort'] === true;
            });

            $defaultSortColumn = !empty($defaultSortColumn) ? reset($defaultSortColumn) : null;

            $this->sortField = $defaultSortColumn
                ? $defaultSortColumn['field']
                : $this->columns()[0]['field'];

            $this->sortDirection = $defaultSortColumn && isset($defaultSortColumn['defaultDirection'])
                ? $defaultSortColumn['defaultDirection']
                : 'asc';
        }
    }

    /**
     * Set the data source as an array.
     *
     * @param array<int, mixed> $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Set the data source as a Select query.
     *
     * @param SelectQuery|Select $select
     */
    public function setSelect(SelectQuery|Select $select)
    {
        $this->select = $select;
    }

    /**
     * Set a transformer callable that processes each row before column rendering.
     *
     * @param callable $transformer
     */
    public function setRowTransformer(callable $transformer): void
    {
        $this->rowTransformer = $transformer;
    }

    /**
     * Sort by a specific field.
     * Toggles sort direction if the same field is sorted again.
     *
     * @param string $field
     * @return void
     */
    public function sortBy(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->page = 1;
    }

    /**
     * Set the current page.
     *
     * @param int $page
     * @return void
     */
    public function setPage(int $page)
    {
        $this->page = max(1, $page);

        $queryParams = request()->all();
        $queryParams['page'] = $page;

        $this->response->header(HtmxResponse::HX_PUSH_URL, url()->addParams($queryParams));
    }

    public function searchChanged()
    {
        $this->search = request()->input('search', $this->search);
        $this->page = 1;

        $queryParams = request()->all();
        $queryParams['search'] = $this->search;
        $queryParams['page'] = 1;

        $this->response->header(HtmxResponse::HX_PUSH_URL, url()->addParams($queryParams));
    }

    /**
     * Set the number of records per page.
     *
     * @param int $perPage
     * @return void
     */
    public function setPerPage(int $perPage)
    {
        $this->perPage = $perPage;
        $this->page = 1;
    }

    /**
     * Build the query or prepare the data array.
     *
     * @return SelectQuery|array<int, mixed>
     * @throws Exception
     */
    protected function buildData()
    {
        if ($this->select) {
            return $this->buildQuery();
        }

        if (is_array($this->data)) {
            return $this->buildArrayData();
        }

        throw new Exception('Data source is not set.');
    }

    /**
     * Build the ORM query using Cycle ORM's Select.
     *
     * @return SelectQuery
     */
    protected function buildQuery(): SelectQuery|Select
    {
        $query = clone $this->select;

        foreach ($this->additionalFilters as $field => $value) {
            if (!empty($value)) {
                $query->where([$field => $value]);
            }
        }

        if ($this->search) {
            $searchableColumns = array_filter($this->columns(), function ($column) {
                return ($column['searchable'] ?? false) && (!empty($column['field']) || !empty($column['searchFields']));
            });

            if (!empty($searchableColumns)) {
                $query->where(function ($q) use ($searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        $searchValue = $this->search;

                        if (isset($column['searchTransform']) && is_callable($column['searchTransform'])) {
                            $searchValue = call_user_func($column['searchTransform'], $searchValue);
                            if ($searchValue === false) {
                                continue;
                            }
                        }

                        if (!empty($column['searchFields']) && is_array($column['searchFields'])) {
                            foreach ($column['searchFields'] as $field) {
                                $q->orWhere($field, 'like', '%' . $searchValue . '%');
                            }
                        } elseif (!empty($column['field'])) {
                            $q->orWhere($column['field'], 'like', '%' . $searchValue . '%');
                        }
                    }
                });
            }
        }

        if (!empty($this->sortField)) {
            $column = collect($this->columns())->firstWhere('field', $this->sortField);
            if ($column && ($column['allowSort'] ?? true)) {
                $query->orderBy($this->sortField, $this->sortDirection);
            }
        }

        return $query;
    }

    /**
     * Prepare and filter the array data.
     *
     * @return array<int, mixed>
     */
    protected function buildArrayData(): array
    {
        $filtered = $this->data;

        foreach ($this->additionalFilters as $field => $value) {
            if (!empty($value)) {
                $filtered = array_filter($filtered, function ($item) use ($field, $value) {
                    return isset($item[$field]) && $item[$field] == $value;
                });
            }
        }

        if (!empty($this->search)) {
            $searchableColumns = array_filter($this->columns(), function ($column) {
                return ($column['searchable'] ?? false) && !empty($column['field']);
            });

            $filtered = array_filter($filtered, function ($item) use ($searchableColumns) {
                foreach ($searchableColumns as $column) {
                    $field = $column['field'];
                    if (isset($item[$field]) && stripos($item[$field], $this->search) !== false) {
                        return true;
                    }
                }

                return false;
            });
        }

        if (!empty($this->sortField)) {
            $column = collect($this->columns())->firstWhere('field', $this->sortField);
            if ($column && ($column['allowSort'] ?? true)) {
                if (isset($column['sortFunction']) && is_callable($column['sortFunction'])) {
                    usort($filtered, function ($a, $b) use ($column) {
                        return call_user_func($column['sortFunction'], $a, $b, $this->sortDirection);
                    });
                } else {
                    usort($filtered, function ($a, $b) {
                        $valueA = $a[$this->sortField] ?? null;
                        $valueB = $b[$this->sortField] ?? null;

                        if ($valueA == $valueB) {
                            return 0;
                        }

                        if ($this->sortDirection === 'asc') {
                            return ($valueA < $valueB) ? -1 : 1;
                        } else {
                            return ($valueA > $valueB) ? -1 : 1;
                        }
                    });
                }
            }
        }

        return array_values($filtered);
    }

    /**
     * Retrieve paginated data.
     *
     * @return array<string, mixed>
     * @throws Exception
     */
    protected function getPaginatedData(): array
    {
        if ($this->select) {
            $query = $this->buildQuery();

            $total = $query->count();
            $pages = max(ceil($total / $this->perPage), 1);
            $currentPage = min($this->page, $pages);

            $data = $query->offset(($currentPage - 1) * $this->perPage)
                ->limit($this->perPage)
                ->fetchAll();

            $data = $this->processData($data);

            return [
                'rows' => $data,
                'total' => $total,
                'pages' => $pages,
                'currentPage' => $currentPage,
            ];
        }

        if (is_array($this->data)) {
            $filteredData = $this->buildArrayData();

            $filteredData = $this->processData($filteredData);

            $total = count($filteredData);
            $pages = max(ceil($total / $this->perPage), 1);
            $currentPage = min($this->page, $pages);

            $offset = ($currentPage - 1) * $this->perPage;
            $data = array_slice($filteredData, $offset, $this->perPage);

            return [
                'rows' => $data,
                'total' => $total,
                'pages' => $pages,
                'currentPage' => $currentPage,
            ];
        }

        throw new Exception('Data source is not set or unsupported.');
    }

    /**
     * Process raw data before rendering.
     * Override this method to implement custom data processing.
     *
     * @param array $data Raw data array
     * @return array Processed data
     */
    protected function processData(array $data): array
    {
        return $data;
    }

    /**
     * Get data for rendering.
     * This method can be overridden to implement custom data retrieval logic.
     *
     * @return array
     * @throws Exception
     */
    protected function getData(): array
    {
        $pagination = $this->getPaginatedData();

        return $pagination['rows'];
    }

    /**
     * Format a single row for display.
     *
     * @param mixed $row Raw row data
     * @return array Formatted row data
     */
    protected function formatRow($row): array
    {
        $formattedRow = [];
        foreach ($this->columns() as $column) {
            if (isset($column['visible']) && !$column['visible']) {
                continue;
            }

            $field = $column['field'] ?? '';
            $renderer = $column['renderer'] ?? null;
            $formatter = $column['formatValue'] ?? null;

            if (is_callable($renderer)) {
                $value = call_user_func($renderer, $row);
            } else {
                if (is_object($row)) {
                    $value = $row->{$field} ?? '';
                } elseif (is_array($row)) {
                    $value = $row[$field] ?? '';
                } else {
                    $value = '';
                }

                if (is_callable($formatter)) {
                    $value = call_user_func($formatter, $value, $row);
                }
            }

            $formattedRow[$field] = $value;
        }

        return $formattedRow;
    }

    /**
     * Render the component.
     *
     * @return mixed
     * @throws Exception
     */
    public function render()
    {
        $pagination = $this->getPaginatedData();
        $rows = array_map([$this, 'formatRow'], $pagination['rows']);

        $displayColumns = array_map(function ($column) {
            return array_merge($column, [
                'class' => $column['class'] ?? '',
                'width' => $column['width'] ?? '',
                'tooltip' => $column['tooltip'] ?? '',
                'visible' => $column['visible'] ?? true,
                'searchable' => $column['searchable'] ?? false,
            ]);
        }, array_filter($this->columns(), function ($column) {
            return !isset($column['visible']) || $column['visible'];
        }));

        return $this->view('flute::partials.modules.table', [
            'columns' => $displayColumns,
            'rows' => $rows,
            'total' => $pagination['total'],
            'pages' => $pagination['pages'],
            'currentPage' => $pagination['currentPage'],
            'perPage' => $this->perPage,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'paginationOptions' => $this->paginationOptions,
        ]);
    }

    public function columns(): array
    {
        return $this->columns;
    }
}
