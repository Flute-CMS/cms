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
        'ajax' => [],
        'autoWidth' => false,
        'language' => [
            'paginate' => [
                'first' => '<i class="ph ph-caret-double-left"></i>',
                'previous' => '<i class="ph ph-caret-left"></i>',
                'next' => '<i class="ph ph-caret-right"></i>',
                'last' => '<i class="ph ph-caret-double-right"></i>'
            ]
        ],
        'order' => []
    ];

    public static bool $initialised = false;
    protected array $phrases = [];

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
            foreach ((array) $entityArray[0] as $key => $value) {
                if (in_array($key, $except))
                    continue;

                $column = new TableColumn($key, $this->getTablePhrase($key));

                $this->checkForType($key, $value, $column);

                $this->addColumn($column);
            }
        }

        $this->setData($entityArray);

        return $this;
    }

    protected function getTablePhrase(string $key)
    {
        return isset($this->phrases[$key]) ? $this->phrases[$key] : ucfirst(__("def.$key"));
    }

    protected function checkForType(string $key, $value, TableColumn $column)
    {
        $this->checkForDate($value, $column);
        $this->checkForId($key, $value, $column);
        $this->checkForImg($key, $value, $column);

        // Check for img, url and etc.
    }

    protected function checkOrder()
    {
        $ordered = false;
        foreach ($this->columns as $key => $val) {
            if ($val->isDefaultOrder()) {
                $ordered = true;

                $this->options['order'][] = [
                    $key,
                    $val->getDefaultOrderType()
                ];
            }
        }

        if (!$ordered) {
            $this->options['order'][] = [
                0,
                'desc'
            ];
        }
    }

    protected function checkForId(string $key, $value, TableColumn $column)
    {
        if ($key === 'id')
            $column->setTitle('ID');
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
    public function withDelete(string $key, string $actionKey = 'deleteaction'): TableBuilder
    {
        $this->addColumn((new TableColumn())->setOrderable(false));
        $action = "deleteDiv.setAttribute('data-$actionKey', data[0]);";
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
                    deleteDiv.setAttribute("data-deletepath", "{{KEY}}");
                    ' . $action . '
                    deleteDiv.setAttribute("data-translate", "def.delete");
                    deleteDiv.setAttribute("data-translate-attribute", "data-tooltip");
                    deleteDiv.setAttribute("data-tooltip-conf", "left");
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
     * Добавление столбца действий с кастомными кнопками.
     *
     * @param string $key
     * @param array $customButtons
     * @return TableBuilder
     */
    public function withActions(string $key, array $customButtons = []): TableBuilder
    {
        $this->addColumn((new TableColumn())->setOrderable(false));
        $this->addColumnDef([
            "targets" => -1,
            "data" => null,
            "render" => [
                'key' => '{{ ACTIONS_BUTTONS }}',
                'js' => $this->generateActionButtonsJs($key, $customButtons)
            ]
        ]);

        return $this;
    }

    /**
     * Генерация JavaScript для кастомных кнопок действий.
     *
     * @param string $key
     * @param array $customButtons
     * @return string
     */
    protected function generateActionButtonsJs(string $key, array $customButtons): string
    {
        $buttonsJs = '';

        foreach ($customButtons as $button) {
            $class = '"' . (is_array($button['class']) ? implode('","', $button['class']) : ($button['class'] ?? '')) . '"';
            $iconClass = $button['iconClass'] ?? '';
            $attributes = '';

            if (isset($button['attributes']) && is_array($button['attributes'])) {
                foreach ($button['attributes'] as $attrKey => $attrValue) {
                    $attributes .= sprintf('customDiv.setAttribute("%s", %s);', $attrKey, $attrValue);
                }
            }

            $buttonsJs .= sprintf('
                let customDiv = make("a");
                customDiv.classList.add("action-button", %s);
                %s
                let customIcon = make("i");
                customIcon.classList.add("ph", "%s");
                customDiv.appendChild(customIcon);
                btnContainer.appendChild(customDiv);
            ',
                $class,
                $attributes,
                $iconClass
            );
        }

        return str_replace('{{KEY}}', $key, '
            function(data, type, full, meta) {
                let btnContainer = make("div");
                btnContainer.classList.add("table-action-buttons");

                let deleteDiv = make("div");
                deleteDiv.classList.add("action-button", "delete");
                deleteDiv.setAttribute("data-translate", "def.delete");
                deleteDiv.setAttribute("data-translate-attribute", "data-tooltip");
                deleteDiv.setAttribute("data-tooltip-conf", "left");
                deleteDiv.setAttribute("data-deleteaction", data[0]);
                deleteDiv.setAttribute("data-deletepath", "{{KEY}}");
                let deleteIcon = make("i");
                deleteIcon.classList.add("ph-bold", "ph-trash");
                deleteDiv.appendChild(deleteIcon);
                btnContainer.appendChild(deleteDiv);

                let changeDiv = make("a");
                changeDiv.classList.add("action-button", "change");
                changeDiv.setAttribute("data-translate", "def.edit");
                changeDiv.setAttribute("data-translate-attribute", "data-tooltip");
                changeDiv.setAttribute("data-tooltip-conf", "left");
                changeDiv.setAttribute("href", u(`admin/{{KEY}}/edit/${data[0]}`));
                let changeIcon = make("i");
                changeIcon.classList.add("ph", "ph-pencil");
                changeDiv.appendChild(changeIcon);
                btnContainer.appendChild(changeDiv);

                ' . $buttonsJs . '

                return btnContainer.outerHTML;
            }
        ');
    }

    protected function generateEmpty(): string
    {
        $div = Html::el('div')->class('table_empty');
        $div->addHtml(__('def.no_results_found'));

        return $div->render();
    }

    /**
     * Рендеринг HTML-таблицы.
     *
     * @return string HTML-код таблицы.
     */
    public function render()
    {
        if (sizeof($this->data) === 0 && !$this->ajaxPath) {
            return $this->generateEmpty();
        }

        // Pretend multiload table styles on 1 page
        if (!self::$initialised) {
            template()->addStyle('tables_css');
            template()->addScript("tables_js");

            self::$initialised = true;
        }

        $this->checkOrder();

        $JSHtml = sprintf("
            <script>
                $(function() {
                    let table = $(document.getElementById('%s'));
                    table.DataTable(%s);
                    
                    // Задержка для предотвращения флекса страницы
                    setTimeout(() => table.removeClass('skeleton'), 200); 
                });
            </script>",
            $this->tableId,
            Json::encode($this->getTableOptions())
        );

        $JSHtml = $this->setAjaxErrorHandler($JSHtml);

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
        $this->columns[] = $column;
        $this->updateColumnDefs($column);
    }

    /**
     * Добавление множества столбцов в таблицу
     * 
     * @param array[TableColumn] $columns
     * @return TableBuilder
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
            $options['ajax']['url'] = $this->ajaxPath;
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

    /**
     * Добавление объединенной колонки.
     *
     * @param string $avatarKey Ключ колонки с аватаркой.
     * @param string $nameKey Ключ колонки с именем.
     * @param string $title Заголовок объединенной колонки.
     * @param string|null $urlKey Ключ колонки с URL (если есть).
     * @param bool $ignoreTab
     * 
     * @return TableBuilder
     */
    public function addCombinedColumn(string $avatarKey, string $nameKey, string $title, ?string $urlKey = null, $ignoreTab = false): TableBuilder
    {
        // Сначала создаем и добавляем отдельные колонки
        $avatarColumn = new TableColumn($avatarKey, 'Avatar');
        $nameColumn = new TableColumn($nameKey, 'Name');
        $this->addColumn($avatarColumn->setVisible(false));
        $this->addColumn($nameColumn->setVisible(false));

        // Определяем позиции колонок
        $avatarPosition = $this->findColumnPosition($avatarKey);
        $namePosition = $this->findColumnPosition($nameKey);
        $urlPosition = $urlKey ? $this->findColumnPosition($urlKey) : null;

        // Затем создаем объединенную колонку
        $combinedColumn = new TableColumn(null, $title);
        $combinedColumn->combined($avatarPosition, $namePosition, $urlPosition, $ignoreTab);

        $this->addColumn($combinedColumn);

        return $this;
    }

    /**
     * Определение позиции колонки по ключу.
     *
     * @param string $key Ключ колонки.
     * @return int Позиция колонки.
     */
    protected function findColumnPosition(string $key): int
    {
        foreach ($this->columns as $index => $column) {
            if ($column->getName() === $key) {
                return $index;
            }
        }
        throw new \Exception("Column with key '{$key}' not found.");
    }

    /**
     * Метод для получения столбца по его идентификатору или индексу.
     *
     * @param string|int $identifier Идентификатор или индекс столбца.
     * @return TableColumn|null Объект столбца или null, если столбец не найден.
     */
    public function getColumn($identifier)
    {
        if (is_int($identifier)) {
            return isset($this->columns[$identifier]) ? $this->columns[$identifier] : null;
        }

        foreach ($this->columns as $column) {
            if ($column instanceof TableColumn && $column->getName() === $identifier) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Метод для обновления параметров столбца.
     *
     * @param string|int $identifier Идентификатор или индекс столбца.
     * @param array $params Массив параметров для обновления.
     * @return void
     */
    public function updateColumn($identifier, array $params): void
    {
        $column = $this->getColumn($identifier);

        if ($column) {
            foreach ($params as $key => $value) {
                $setterMethod = 'set' . ucfirst($key);
                if (method_exists($column, $setterMethod)) {
                    $column->$setterMethod($value);
                }
            }

            // Обновить columnDefs, если столбец был модифицирован
            $this->updateColumnDefs($column);
        }
    }

    public function setPhrases(array $phrases): TableBuilder
    {
        $this->phrases = $phrases;

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

        $this->options['ajax']['url'] = $this->ajaxPath;
        $this->options['ajax']['error'] = "{{ERROR_HANDLER}}";
        $this->options['serverSide'] = true;
        $this->options['processing'] = true;
    }

    protected function setAjaxErrorHandler(string $html)
    {
        $encodedHTML = str_replace('"{{ERROR_HANDLER}}"', 'function (jqXHR, textStatus, errorThrown) {
            toast({
                message:
                    jqXHR.responseJSON?.error ?? translate("def.unknown_error"),
                type: "error",
            });
        }', $html);

        return $encodedHTML;
    }

    protected function generateHtml(): string
    {
        $overflowDiv = Html::el('div')->addClass('overflow-table');
        $table = Html::el('table')->id($this->tableId)->addClass("{$this->tableClass} skeleton");

        if (!empty($this->columns)) {
            $thead = Html::el('thead');
            $tr = Html::el('tr');
            foreach ($this->columns as $column) {
                $tr->create('th')->addText($column->getTitle());
            }
            $thead->addHtml($tr);
            $table->addHtml($thead);
        }

        if (!empty($this->data)) {
            $tbody = Html::el('tbody');
            foreach ($this->data as $row) {
                $tr = Html::el('tr');
                foreach ($this->columns as $column) {
                    $columnData = (is_string($column->getName()) || is_int($column->getName())) ? $column->getName() : null;
                    if ($columnData !== null && isset($row[$columnData])) {
                        $td = $tr->create('td');
                        if ((bool) $column->getClean() === true) {
                            $td->addText(
                                is_array($row[$columnData]) ?
                                Json::encode($row[$columnData]) :
                                $row[$columnData]
                            );
                        } else {
                            $td->addHtml(
                                is_array($row[$columnData]) ?
                                Json::encode($row[$columnData]) :
                                $row[$columnData]
                            );
                        }
                    } else {
                        $tr->create('td', '');
                    }
                }
                $tbody->addHtml($tr);
            }
            $table->addHtml($tbody);
        }

        $overflowDiv->addHtml($table);

        return $overflowDiv->render();
    }

    protected function generateTableId(): void
    {
        $this->tableId = Random::generate(20);
    }
}