<?php

namespace Flute\Core\Table;

use Nette\Utils\Random;

/**
 * Класс TableColumn представляет колонку в таблице.
 */
class TableColumn
{
    /**
     * @var string|null Имя столбца (key).
     */
    private ?string $name;

    /**
     * @var string|null Заголовок столбца.
     */
    private ?string $title;

    /**
     * @var mixed Данные столбца.
     */
    private $data;

    /**
     * @var bool Видимость столбца.
     */
    private bool $visible = true;

    /**
     * @var bool Возможность сортировки столбца.
     */
    private bool $orderable = true;

    /**
     * @var bool Нужно ли по стандарту использовать эту колонку для order'а
     */
    private bool $defaultOrder = false;

    /**
     * @var string desc / asc
     */
    private string $defaultOrderType = 'desc';

    /**
     * @var bool Возможность поиска по столбцу.
     */
    private bool $searchable = true;

    /**
     * @var string|null Класс CSS для столбца.
     */
    private ?string $className;

    /**
     * @var mixed Содержимое по умолчанию для столбца.
     */
    private $defaultContent;

    /**
     * @var mixed Тип данных столбца.
     */
    private $type;

    /**
     * @var array Как будет рендерится колонка.
     */
    private array $render = [];

    /**
     * @var bool Нужно ли сериализировать значения
     */
    private bool $clean = true;

    /**
     * Конструктор для создания объекта TableColumn.
     *
     * @param mixed $name Имя столбца.
     * @param mixed $title Заголовок столбца.
     */
    public function __construct($name = null, $title = null)
    {
        $this->name = $name;
        $this->title = $title ?? $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getData()
    {
        return $this->data;
    }

    public function isVisible()
    {
        return $this->visible;
    }

    public function isOrderable()
    {
        return $this->orderable;
    }

    public function isSearchable()
    {
        return $this->searchable;
    }

    public function isDefaultOrder(): bool
    {
        return $this->defaultOrder;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getDefaultContent()
    {
        return $this->defaultContent;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getRender()
    {
        return $this->render;
    }

    public function getDefaultOrderType()
    {
        return $this->defaultOrderType;
    }
    
    public function getClean() : bool
    {
        return $this->clean;
    }

    public function image(bool $rounded = true)
    {
        $class = $rounded ? ' rounded' : '';

        $random = Random::generate(10);

        $this->setType('html')
            ->setSearchable(false)
            ->setOrderable(false)
            ->setClassName('img-avatar' . $class);

        return $this->setRender("{{ JS_RENDER_$random }}", 'function (data, type) {
            let image = (data.startsWith("http://") || data.startsWith("https://")) ? data : u(data);
            return `<img class=\'img\' src=\'${image}\' />`;
        }');
    }

    /**
     * Настройка рендеринга для колонки типа URL.
     *
     * @param int $textKey Ключ для отображаемого текста.
     * @param int $urlKey Ключ для URL.
     * 
     * @return TableColumn
     */
    public function url(int $textKey, int $urlKey): TableColumn
    {
        $random = Random::generate(10);

        $this->setType('html')
            ->setSearchable(false)
            ->setOrderable(false)
            ->setClassName('url-column');

        $renderJs = '
            function(data, type, full, meta) {
                let textData = full["' . $textKey . '"];
                let urlData = full["' . $urlKey . '"];
                return `<a href="${urlData}" class="url-content">${textData}</a>`;
            }
        ';

        return $this->setRender("{{ JS_RENDER_URL_$random }}", $renderJs);
    }

    /**
     * Настройка рендеринга для объединенной колонки.
     *
     * @param int $avatarKey Позиция колонки с аватаркой.
     * @param int $nameKey Позиция колонки с именем.
     * @param int|null $urlKey Позиция колонки с URL (если есть).
     * @param bool $ignoreTab
     * 
     * @return TableColumn
     */
    public function combined(int $avatarKey, int $nameKey, ?int $urlKey = null, $ignoreTab = false): TableColumn
    {
        $random = Random::generate(10);

        $this->setType('html')
            ->setSearchable(false)
            ->setOrderable(false)
            ->setClassName('combined-column');

        $stringKey = $urlKey ? $urlKey : '0';

        $renderJs = '
            function(data, type, full, meta) {
                let avatarData = full[' . $avatarKey . '];
                let nameData = full[' . $nameKey . '];
                let image = (avatarData?.startsWith("http://") || avatarData?.startsWith("https://")) ? avatarData : u(avatarData);
                let contentHtml;
                if (' . ($urlKey !== null ? 'true' : 'false') . ') {
                    contentHtml = `<a '.($ignoreTab === false ? 'data-tab' : '') .' data-miniprofile href="` + full[' . $stringKey . '] + `" class="combined-content">
                                      <span class="name">${nameData}</span>
                                      <small>' . __('def.goto') . '</small>
                                   </a>`;
                } else {
                    contentHtml = `<div class="combined-content">
                                      <span class="name">${nameData}</span>
                                   </div>`;
                }
                return `<div class="combined-cell">
                    <img class="img-avatar" src="${image}" />
                    ${contentHtml}
                </div>`;
            }
        ';

        return $this->setRender("{{ JS_RENDER_COMBINED_$random }}", $renderJs);
    }

    public function date()
    {
        $this->setType('html')
            ->setSearchable(false)
            // ->setOrderable(false)
            ->setClassName('table-date');

        return $this->setRender("{{ DATE }}", 'function (data, type) {
            try {
                let json = JSON.parse(data);
        
                let resultDate = new Date(json?.date);
        
                let formattedDate = resultDate.toLocaleString("ru-RU", {
                    day: "2-digit",
                    month: "2-digit",
                    year: "numeric",
                    hour: "2-digit",
                    minute: "2-digit",
                    second: "2-digit"
                });
        
                return formattedDate.replace(/(\d+).(\d+).(\d+), (\d+):(\d+):(\d+)/, "$1.$2.$3 $4:$5:$6");
            } catch (e) {
                return data;
            }
        }');
    }

    public function setRender(string $key, $render)
    {
        $this->render = [
            'key' => $key,
            'js' => $render
        ];

        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function setVisible($visible)
    {
        $this->visible = $visible;
        return $this;
    }

    public function setOrderable($orderable)
    {
        $this->orderable = $orderable;
        return $this;
    }

    public function setSearchable($searchable)
    {
        $this->searchable = $searchable;
        return $this;
    }

    public function setClassName($className)
    {
        $this->className = $className;
        return $this;
    }

    public function setDefaultContent($defaultContent)
    {
        $this->defaultContent = $defaultContent;
        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function setDefaultOrder()
    {
        $this->defaultOrder = true;
        return $this;
    }

    public function setDefaultOrderType(string $type)
    {
        $this->defaultOrderType = $type;
        return $this;
    }

    public function setClean(bool $clean)
    {
        $this->clean = $clean;
        return $this;
    }

    /**
     * Преобразует объект TableColumn в ассоциативный массив.
     *
     * @return array Ассоциативный массив свойств столбца.
     */
    public function toArray()
    {
        $column = [
            'name' => $this->name,
            'title' => $this->title,
            'visible' => $this->visible,
            'orderable' => $this->orderable,
            'searchable' => $this->searchable,
            'isDefaultOrder' => $this->isDefaultOrder(),
            'defaultOrderType' => $this->getDefaultOrderType(),
            'needClean' => $this->getClean()
        ];

        if ($this->render !== null) {
            $column['render'] = $this->render;
        }

        if (!empty($this->data)) {
            $column['data'] = $this->data;
        }

        if (!empty($this->className)) {
            $column['className'] = $this->className;
        }

        if (!empty($this->defaultContent)) {
            $column['defaultContent'] = $this->defaultContent;
        }

        if (!empty($this->type)) {
            $column['type'] = $this->type;
        }

        return $column;
    }
}