<?php

namespace Flute\Core\Table;

use Nette\Utils\Html;
use Nette\Utils\Json;
use Nette\Utils\Random;

/**
 * Class TableBuilder
 * Builds and renders HTML table with specified options and columns.
 */
class TableBuilder
{
    /**
     * @var string Идентификатор таблицы.
     */
    protected string $tableId = '';

    /**
     * @var string Класс таблицы.
     */
    protected string $tableClass = 'admin-table';

    /**
     * @var string Путь для AJAX-запросов.
     */
    protected ?string $ajaxPath = null;

    /**
     * @var string Раздел для рендеринга.
     */
    protected ?string $section = null;

    /**
     * @var array[TableColumn] Массив столбцов таблицы.
     */
    protected array $columns = [];

    /**
     * @var array Данные для таблицы.
     */
    protected array $data = [];

    /**
     * @var array Опции конфигурации DataTables.
     */
    protected array $options = [
        'ajax' => '',
        'language' => [
            'paginate' => [
                'first' => '<i class="ph ph-caret-double-left"></i>',
                'previous' => '<i class="ph ph-caret-left"></i>',
                'next' => '<i class="ph ph-caret-right"></i>',
                'last' => '<i class="ph ph-caret-double-right"></i>'
            ]
        ]
    ];

    public static bool $initialised = false;

    /**
     * TableBuilder constructor.
     * 
     * @param string $section The section where the table will be used.
     * @param string|null $ajaxPath The AJAX path for loading table data.
     */
    public function __construct(string $ajaxPath = null, string $section = null)
    {
        $this->ajaxPath = $ajaxPath;
        $this->section = $section;

        $this->generateTableId();
        $this->setTableDefaultOptions();
    }

    /**
     * Создание столбцов таблицы на основе массива данных сущности.
     *
     * @param array $entityArray Массив данных сущности.
     * @return TableBuilder
     */
    public function fromEntity($entityArray, array $except = []): TableBuilder
    {
        if (empty($entityArray)) {
            return $this;
        }

        if (isset($entityArray[0])) {
            foreach ($entityArray[0] as $key => $value) {
                if (in_array($key, $except))
                    continue;

                $column = new TableColumn($key, ucfirst(__("def.$key")));

                $this->checkForType($key, $value, $column);

                $this->addColumn($column);
            }
        }

        $this->setData($entityArray);

        return $this;
    }

    protected function checkForType(string $key, $value, TableColumn $column)
    {
        $this->checkForDate($value, $column);
        $this->checkForId($key, $value, $column);
        $this->checkForImg($key, $value, $column);

        // Check for img, url and etc.
    }

    protected function checkForId(string $key, $value, TableColumn $column)
    {
        // if( $key === 'id' ) $column->setType('number');
    }

    protected function checkForDate($value, TableColumn $column)
    {
        if ($value instanceof \DateTimeImmutable)
            $column->date();
    }

    protected function checkForImg(string $key, $value, TableColumn $column)
    {
        if (in_array($key, ['avatar', 'picture', 'img', 'image']))
            $column->image($key === 'avatar');
    }

    /**
     * Добавление столбца удаление.
     *
     * @return TableBuilder
     */
    public function withDelete(string $key): TableBuilder
    {
        $this->addColumn((new TableColumn())->setOrderable(false));
        $this->addColumnDef([
            "targets" => -1,
            "data" => null,
            "render" => [
                'key' => '{{ DELETE_BUTTON }}',
                'js' => str_replace('{{KEY}}', $key, '
                function(data, type, full, meta) {
                    let btnContainer = make("div");
                    btnContainer.classList.add("table-action-buttons");

                    let deleteDiv = make("div");
                    deleteDiv.classList.add("action-button", "delete");
                    deleteDiv.setAttribute("data-tooltip", translate("def.delete"));
                    deleteDiv.setAttribute("data-deleteaction", data[0]);
                    deleteDiv.setAttribute("data-deletepath", "{{KEY}}");
                    let deleteIcon = make("i");
                    deleteIcon.classList.add("ph-bold", "ph-trash");
                    deleteDiv.appendChild(deleteIcon);
                    btnContainer.appendChild(deleteDiv);
    
                    return btnContainer.outerHTML;
                }
                ')
            ]
        ]);

        return $this;
    }

    /**
     * Добавление столбца действий.
     *
     * @return TableBuilder
     */
    public function withActions(string $key): TableBuilder
    {
        $this->addColumn((new TableColumn())->setOrderable(false));
        $this->addColumnDef([
            "targets" => -1,
            "data" => null,
            "render" => [
                'key' => '{{ ACTIONS_BUTTONS }}',
                'js' => str_replace('{{KEY}}', $key, '
                function(data, type, full, meta) {
                    let btnContainer = make("div");
                    btnContainer.classList.add("table-action-buttons");

                    let deleteDiv = make("div");
                    deleteDiv.classList.add("action-button", "delete");
                    deleteDiv.setAttribute("data-tooltip", translate("def.delete"));
                    deleteDiv.setAttribute("data-deleteaction", data[0]);
                    deleteDiv.setAttribute("data-deletepath", "{{KEY}}");
                    let deleteIcon = make("i");
                    deleteIcon.classList.add("ph-bold", "ph-trash");
                    deleteDiv.appendChild(deleteIcon);
                    btnContainer.appendChild(deleteDiv);

                    let changeDiv = make("a");
                    changeDiv.classList.add("action-button", "change");
                    changeDiv.setAttribute("data-tooltip", translate("def.edit"));
                    changeDiv.setAttribute("href", u(`admin/{{KEY}}/edit/${data[0]}`));
                    let changeIcon = make("i");
                    changeIcon.classList.add("ph", "ph-pencil");
                    changeDiv.appendChild(changeIcon);
                    btnContainer.appendChild(changeDiv);
    
                    return btnContainer.outerHTML;
                }
                ')
            ]
        ]);

        return $this;
    }

    /**
     * Рендеринг HTML-таблицы.
     *
     * @return string HTML-код таблицы.
     */
    public function render()
    {
        // Pretend multiload table styles on 1 page
        if (!self::$initialised) {
            template()->addStyle('tables_css');
            template()->addScript("tables_js");

            self::$initialised = true;
        }

        $JSHtml = sprintf("
            <script>
                let table = $('#%s');
                table.DataTable(%s);
                table.removeClass('skeleton')
            </script>",
            $this->tableId,
            Json::encode($this->getTableOptions())
        );

        template()->section(
            "footer",
            $this->setJSRenderers($JSHtml)
        );

        return $this->generateHtml();
    }

    /**
     * Добавление столбца в таблицу.
     *
     * @param TableColumn $column Объект столбца.
     */
    public function addColumn(TableColumn $column)
    {
        $this->columns[] = $column->toArray();
        $this->updateColumnDefs($column);
    }

    /**
     * Добавление множества столбцов в таблицу
     * 
     * @param array[TableColumn] $column
     */
    public function addColumns(array $columns): self
    {
        foreach ($columns as $column) {
            $this->addColumn($column);
        }

        return $this;
    }

    /**
     * Установка данных для таблицы.
     *
     * @param array|object $data Данные для таблицы.
     * @return TableBuilder Возвращает экземпляр класса для цепочки вызовов.
     */
    public function setData($data): TableBuilder
    {
        $this->data = $this->convertToArray($data);

        $this->options['ajax'] = '';
        $this->options['serverSide'] = false;
        $this->options['processing'] = false;

        return $this;
    }

    public function getTableId(): string
    {
        return $this->tableId;
    }

    public function getTableClass(): string
    {
        return $this->tableClass;
    }

    public function setTableClass(string $tableClass): TableBuilder
    {
        $this->tableClass = $tableClass;
        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get all table options for JS
     * 
     * @return array
     */
    public function getTableOptions(): array
    {
        $options = $this->options;

        if (!empty($this->data)) {
            unset($options['ajax']);
            unset($options['processing']);
        } else if (!empty($this->ajaxPath)) {
            $options['ajax'] = ['url' => $this->ajaxPath];
        }

        if (!empty($options['columnDefs'])) {
            foreach ($options['columnDefs'] as $key => $val) {
                if (!isset($options['columnDefs'][$key]['render']['key'])) {
                    unset($options['columnDefs'][$key]['render']);
                    continue;
                }

                $options['columnDefs'][$key]['render'] = $options['columnDefs'][$key]['render']['key'];
            }
        }

        return $options;
    }

    public function setColumns(array $columns): TableBuilder
    {
        $this->columns = $columns;
        return $this;
    }

    public function setTableId(string $tableId): TableBuilder
    {
        $this->tableId = $tableId;
        return $this;
    }

    public function setTableLang(string $lang): TableBuilder
    {
        $this->options['language']['url'] = sprintf('https://cdn.datatables.net/plug-ins/1.13.7/i18n/%s.json', $lang);

        return $this;
    }

    public function addColumnDef(array $columnDef): TableBuilder
    {
        $this->options['columnDefs'][] = $columnDef;

        return $this;
    }

    public function setAjaxPath(string $ajaxPath): TableBuilder
    {
        $this->ajaxPath = $ajaxPath;

        return $this;
    }

    public function responsive(bool $responsive): TableBuilder
    {
        $this->options['responsive'] = $responsive;

        return $this;
    }

    public function stateSave(bool $stateSave): TableBuilder
    {
        $this->options['stateSave'] = $stateSave;

        return $this;
    }

    public function pagingType(string $pagingType): TableBuilder
    {
        if (!in_array($pagingType, ['numbers', 'simple', 'simple_numbers', 'full', 'full_numbers', 'first_last_numbers']))
            throw new \InvalidArgumentException("Paging type {$pagingType} is invalid!");

        $this->options['pagingType'] = $pagingType;

        return $this;
    }

    public function __get($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    public function __set($name, $value)
    {
        $this->options[$name] = $value;
    }

    protected function setJSRenderers(string $encodedHTML): string
    {
        foreach ($this->options['columnDefs'] as $column) {
            if (!isset($column['render']))
                continue;

            $renderer = $column['render'];

            if (!empty($renderer)) {
                $encodedHTML = str_replace('"' . $renderer['key'] . '"', $renderer['js'], $encodedHTML);
            }
        }

        return $encodedHTML;
    }

    protected function convertToArray($object): array
    {
        return json_decode(json_encode($object), true);
    }

    /**
     * Updates a 'columnDefs' in the DataTables.js
     * 
     * @param TableColumn $column
     * 
     * @return void
     */
    protected function updateColumnDefs(TableColumn $column): void
    {
        $columnArray = $column->toArray();
        $isModified = false;

        if (
            $columnArray['visible'] !== true || $columnArray['orderable'] !== true || $columnArray['searchable'] !== true ||
            isset($columnArray['data']) || isset($columnArray['className']) ||
            isset($columnArray['defaultContent']) || isset($columnArray['type']) ||
            isset($columnArray['render'])
        ) {
            $isModified = true;
        }

        if ($isModified) {
            $columnDef = ['targets' => count($this->columns) - 1] + $columnArray;
            $this->addColumnDef($columnDef);
        }
    }

    protected function setTableDefaultOptions(): void
    {
        $this->setTableLang(table_lang());

        $this->options['ajax'] = $this->ajaxPath;
        $this->options['serverSide'] = true;
        $this->options['processing'] = true;
    }

    protected function generateHtml(): string
    {
        $table = Html::el('table')->id($this->tableId)->addClass("{$this->tableClass} skeleton");

        if (!empty($this->columns)) {
            $thead = Html::el('thead');
            $tr = Html::el('tr');
            foreach ($this->columns as $column) {
                $tr->create('th')->addText($column['title']);
            }
            $thead->addHtml($tr);
            $table->addHtml($thead);
        }

        if (!empty($this->data)) {
            $tbody = Html::el('tbody');
            foreach ($this->data as $row) {
                $tr = Html::el('tr');
                foreach ($this->columns as $column) {
                    $columnData = isset($column['name']) && (is_string($column['name']) || is_int($column['name'])) ? $column['name'] : null;
                    if ($columnData !== null && isset($row[$columnData])) {
                        $tr->create('td')
                            ->addText(
                                is_array($row[$columnData]) ?
                                Json::encode($row[$columnData]) :
                                $row[$columnData]
                            );
                    } else {
                        $tr->create('td', '');
                    }
                }
                $tbody->addHtml($tr);
            }
            $table->addHtml($tbody);
        }

        return $table->render();
    }

    protected function generateTableId(): void
    {
        $this->tableId = Random::generate(20);
    }
}