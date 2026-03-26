<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;

/**
 * Filters Layout - универсальный компонент фильтрации для таблиц и списков.
 *
 * Поддерживает:
 * - ButtonGroup фильтры (с label)
 * - Select фильтры (single/multiple)
 * - Input фильтры (text, number, date)
 * - DateRange фильтры (от/до)
 * - Checkbox фильтры
 */
class Filters extends Layout
{
    protected $template = 'admin::partials.layouts.filters';

    /**
     * Массив фильтров.
     */
    protected array $filters = [];

    /**
     * Показывать кнопку сброса.
     */
    protected bool $showReset = true;

    /**
     * Использовать yoyo для обновления.
     */
    protected bool $yoyo = true;

    /**
     * Компактный режим (в одну строку).
     */
    protected bool $compact = false;

    /**
     * Создать экземпляр Filters.
     */
    public static function make(): self
    {
        return new static();
    }

    /**
     * Добавить ButtonGroup фильтр.
     *
     * @param string $name    Имя параметра
     * @param string $label   Метка фильтра
     * @param array  $options Опции [value => label] или [value => ['label' => '', 'icon' => '']]
     * @param mixed  $default Значение по умолчанию
     */
    public function buttonGroup(string $name, string $label, array $options, mixed $default = null): self
    {
        $this->filters[] = [
            'type' => 'buttonGroup',
            'name' => $name,
            'label' => $label,
            'options' => $this->normalizeButtonGroupOptions($options),
            'value' => request()->input($name, $default),
            'default' => $default,
        ];

        return $this;
    }

    /**
     * Добавить Select фильтр.
     *
     * @param string $name        Имя параметра
     * @param string $label       Метка фильтра
     * @param array  $options     Опции [value => label]
     * @param mixed  $default     Значение по умолчанию
     * @param bool   $multiple    Множественный выбор
     * @param bool   $allowEmpty  Разрешить пустое значение
     */
    public function select(
        string $name,
        string $label,
        array $options,
        mixed $default = null,
        bool $multiple = false,
        bool $allowEmpty = true,
    ): self {
        $this->filters[] = [
            'type' => 'select',
            'name' => $name,
            'label' => $label,
            'options' => $options,
            'value' => request()->input($name, $default),
            'default' => $default,
            'multiple' => $multiple,
            'allowEmpty' => $allowEmpty,
        ];

        return $this;
    }

    /**
     * Добавить Input фильтр.
     *
     * @param string $name        Имя параметра
     * @param string $label       Метка фильтра
     * @param string $type        Тип input (text, number, date, email)
     * @param mixed  $default     Значение по умолчанию
     * @param string $placeholder Placeholder
     */
    public function input(
        string $name,
        string $label,
        string $type = 'text',
        mixed $default = null,
        string $placeholder = '',
    ): self {
        $this->filters[] = [
            'type' => 'input',
            'inputType' => $type,
            'name' => $name,
            'label' => $label,
            'value' => request()->input($name, $default),
            'default' => $default,
            'placeholder' => $placeholder,
        ];

        return $this;
    }

    /**
     * Добавить DateRange фильтр (от/до).
     *
     * @param string $name    Базовое имя параметра (создаст {name}_from и {name}_to)
     * @param string $label   Метка фильтра
     * @param array  $default Значения по умолчанию ['from' => '', 'to' => '']
     */
    public function dateRange(string $name, string $label, array $default = []): self
    {
        $this->filters[] = [
            'type' => 'dateRange',
            'name' => $name,
            'label' => $label,
            'valueFrom' => request()->input($name . '_from', $default['from'] ?? null),
            'valueTo' => request()->input($name . '_to', $default['to'] ?? null),
            'default' => $default,
        ];

        return $this;
    }

    /**
     * Добавить Checkbox фильтр.
     *
     * @param string $name    Имя параметра
     * @param string $label   Метка фильтра
     * @param bool   $default Значение по умолчанию
     */
    public function checkbox(string $name, string $label, bool $default = false): self
    {
        $value = request()->input($name);
        $this->filters[] = [
            'type' => 'checkbox',
            'name' => $name,
            'label' => $label,
            'value' => $value !== null ? (bool) $value : $default,
            'default' => $default,
        ];

        return $this;
    }

    /**
     * Добавить предустановленный фильтр периодов.
     *
     * @param string $name    Имя параметра
     * @param string $label   Метка фильтра
     * @param string $default Значение по умолчанию
     */
    public function period(string $name = 'period', string $label = '', string $default = '7d'): self
    {
        $options = [
            '7d' => __('admin.filters.periods.7d'),
            '30d' => __('admin.filters.periods.30d'),
            '90d' => __('admin.filters.periods.90d'),
            '180d' => __('admin.filters.periods.180d'),
            '365d' => __('admin.filters.periods.365d'),
            'all' => __('admin.filters.periods.all'),
        ];

        return $this->buttonGroup($name, $label ?: __('admin.filters.period'), $options, $default);
    }

    /**
     * Добавить предустановленный фильтр статуса (вкл/выкл/все).
     *
     * @param string $name    Имя параметра
     * @param string $label   Метка фильтра
     * @param string $default Значение по умолчанию
     */
    public function status(string $name = 'status', string $label = '', string $default = 'all'): self
    {
        $options = [
            'all' => __('admin.filters.status.all'),
            'active' => __('admin.filters.status.active'),
            'inactive' => __('admin.filters.status.inactive'),
        ];

        return $this->buttonGroup($name, $label ?: __('admin.filters.status_label'), $options, $default);
    }

    /**
     * Скрыть кнопку сброса.
     */
    public function withoutReset(): self
    {
        $this->showReset = false;

        return $this;
    }

    /**
     * Отключить yoyo.
     */
    public function withoutYoyo(): self
    {
        $this->yoyo = false;

        return $this;
    }

    /**
     * Включить компактный режим.
     */
    public function compact(bool $compact = true): self
    {
        $this->compact = $compact;

        return $this;
    }

    /**
     * Получить значение фильтра.
     */
    public function getValue(string $name): mixed
    {
        foreach ($this->filters as $filter) {
            if ($filter['name'] === $name) {
                return $filter['value'] ?? $filter['default'] ?? null;
            }
        }

        return null;
    }

    /**
     * Получить все значения фильтров.
     */
    public function getValues(): array
    {
        $values = [];
        foreach ($this->filters as $filter) {
            if ($filter['type'] === 'dateRange') {
                $values[$filter['name'] . '_from'] = $filter['valueFrom'];
                $values[$filter['name'] . '_to'] = $filter['valueTo'];
            } else {
                $values[$filter['name']] = $filter['value'] ?? $filter['default'] ?? null;
            }
        }

        return $values;
    }

    /**
     * Проверить, есть ли активные фильтры (отличающиеся от default).
     */
    public function hasActiveFilters(): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter['type'] === 'dateRange') {
                if (!empty($filter['valueFrom']) || !empty($filter['valueTo'])) {
                    return true;
                }
            } elseif (( $filter['value'] ?? null ) !== ( $filter['default'] ?? null )) {
                return true;
            }
        }

        return false;
    }

    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (!$this->isVisible()) {
            return;
        }

        return view($this->template, [
            'filters' => $this->filters,
            'showReset' => $this->showReset,
            'hasActiveFilters' => $this->hasActiveFilters(),
            'yoyo' => $this->yoyo,
            'compact' => $this->compact,
        ]);
    }

    /**
     * Нормализовать опции для ButtonGroup.
     */
    protected function normalizeButtonGroupOptions(array $options): array
    {
        $normalized = [];
        foreach ($options as $value => $option) {
            if (is_string($option)) {
                $normalized[$value] = ['label' => $option, 'icon' => null, 'tooltip' => null];
            } elseif (is_array($option)) {
                $normalized[$value] = [
                    'label' => $option['label'] ?? null,
                    'icon' => $option['icon'] ?? null,
                    'tooltip' => $option['tooltip'] ?? null,
                ];
            }
        }

        return $normalized;
    }
}
