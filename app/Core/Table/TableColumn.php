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

    public function isDefaultOrder() : bool
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

    public function image(bool $rounded = true)
    {
        $class = $rounded ? ' rounded' : '';

        $random = Random::generate(10);

        $this->setType('html')
            ->setSearchable(false)
            ->setOrderable(false)
            ->setClassName('img-avatar' . $class);

        return $this->setRender("{{ JS_RENDER_$random }}", 'function (data, type) {
            console.log(data)
            let image = (data.startsWith("http://") || data.startsWith("https://")) ? data : u(data);
            return `<img class=\'img\' src=\'${image}\' />`;
        }');
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