<?php

namespace Flute\Admin\Platform\Fields;

use Cycle\ORM\Select\Repository;
use Flute\Admin\Packages\Search\SelectRegistry;
use Flute\Admin\Platform\Concerns\ComplexFieldConcern;
use Flute\Admin\Platform\Concerns\Multipliable;
use Flute\Admin\Platform\Field;
use ReflectionEnum;
use ReflectionException;
use Throwable;
use Traversable;

/**
 * Class Select.
 *
 * @method Select accesskey($value = true)
 * @method Select autofocus($value = true)
 * @method Select disabled($value = true)
 * @method Select form($value = true)
 * @method Select name(string $value = null)
 * @method Select required(bool $value = true)
 * @method Select size($value = true)
 * @method Select tabindex($value = true)
 * @method Select help(string $value = null)
 * @method Select popover(string $value = null)
 * @method Select options($value = null)
 * @method Select title(string $value = null)
 * @method Select maximumSelectionLength(int $value = 0)
 * @method Select allowAdd($value = true)
 */
class Select extends Field implements ComplexFieldConcern
{
    use Multipliable;

    /**
     * View template
     */
    protected $view = 'admin::partials.fields.select';

    /**
     * Default configuration
     */
    protected array $config = [
        'mode' => 'static', // static|database|async
        'multiple' => false, // multiple selection mode
        'maxItems' => 1, // maximum number of items that can be selected
        'uiSearch' => null, // null=auto, true=always, false=never (UI only)
        'searchThreshold' => 6, // auto-enable UI search when options > threshold (static mode)
        'searchable' => false, // enable search (async mode helper)
        'minSearchLength' => 2, // minimum search query length
        'searchDelay' => 300, // search delay in milliseconds
        'preload' => false, // preload options in async mode
        'clearButton' => true, // show clear button
        'removeButton' => false, // show remove button
        'allowEmpty' => false, // allow empty option
        'allowAdd' => false, // allow adding new options
        'positioning' => 'dropdown', // dropdown|aligned
        'plugins' => [], // default plugins (UI search plugin is enabled dynamically)
        'renderOption' => null, // custom option render function
        'renderItem' => null, // custom item render function
        'renderNoResults' => null, // custom no results render function
        'filter' => null, // custom filter function
        'extraFields' => [], // extra entity fields to include as option metadata
        'optionView' => null, // Blade view name for option rendering
        'itemView' => null, // Blade view name for item rendering
    ];

    /**
     * Database configuration
     */
    protected array $databaseConfig = [
        'entity' => null, // entity alias
        'displayField' => null, // display field name
        'valueField' => 'id', // value field name
        'searchFields' => [], // fields to search in
        'conditions' => [], // additional query conditions
        'orderBy' => null, // order by field and direction
        'limit' => 20, // query limit
    ];

    /**
     * Default attributes value
     */
    protected $attributes = [
        'class' => 'form-control',
        'data-select' => '',
        'yoyo' => false,
    ];

    /**
     * Attributes available for a particular tag
     */
    protected $inlineAttributes = [
        'accesskey',
        'autofocus',
        'disabled',
        'form',
        'name',
        'required',
        'placeholder',
        'size',
        'tabindex',
        'multiple',
        'data-select',
        'data-mode',
        'data-search-url',
        'data-search-min-length',
        'data-search-delay',
        'data-search-fields',
        'data-entity',
        'data-display-field',
        'data-value-field',
        'data-max-items',
        'data-preload',
        'data-plugins',
        'data-allow-add',
        'data-searchable',
        'data-search-threshold',
        'data-extra-fields',
        'data-option-view',
        'data-item-view',
        'data-positioning',
    ];

    public function __construct()
    {
        $this->addBeforeRender(function () {
            $this->configureSelect();
        });
    }

    /**
     * @throws ReflectionException
     */
    public function fromEnum(string $enum, ?string $displayName = null): self
    {
        $reflection = new ReflectionEnum($enum);
        $options = [];
        foreach ($enum::cases() as $item) {
            $key = $reflection->isBacked() ? $item->value : $item->name;
            $options[$key] = is_null($displayName) ? __($item->name) : $item->$displayName();
        }
        $this->set('options', $options);

        return $this->addBeforeRender(function () use ($reflection, $enum) {
            $value = [];
            collect($this->get('value'))->each(static function ($item) use (&$value, $reflection, $enum) {
                if ($item instanceof $enum) {
                    /* @var \UnitEnum $item */
                    $value[] = $reflection->isBacked() ? $item->value : $item->name;
                } else {
                    $value[] = $item;
                }
            });
            $this->set('value', $value);
        });
    }

    public function empty(string $name = '', string $key = ''): self
    {
        return $this->addBeforeRender(function () use ($name, $key) {
            $options = $this->get('options', []);

            if (!is_array($options)) {
                $options = $options->toArray();
            }

            $value = [$key => $name] + $options;

            $this->set('options', $value);
            $this->set('allowEmpty', '1');
        });
    }

    /**
     * @return self
     */
    public function taggable()
    {
        return $this->set('tags', true);
    }

    public function yoyo()
    {
        return $this->set('yoyo', true);
    }

    /**
     * Set static options
     */
    public function options(array $options): self
    {
        return $this->set('options', $options)->setConfig('mode', 'static');
    }

    /**
     * Configure database source
     */
    public function fromDatabase(
        string $entity,
        string $displayField,
        ?string $valueField = 'id',
        ?array $searchFields = null,
        ?array $conditions = null,
    ): self {
        return $this->setDatabaseConfigs([
            'entity' => $entity,
            'displayField' => $displayField,
            'valueField' => $valueField,
            'searchFields' => $searchFields,
            'conditions' => $conditions,
        ])->setConfig('mode', 'database');
    }

    /**
     * Make select async searchable
     */
    public function searchable(bool $searchable = true, ?int $minLength = 2, ?int $delay = 300): self
    {
        return $this->setConfigs([
            'searchable' => $searchable,
            'minSearchLength' => $minLength,
            'searchDelay' => $delay,
            'mode' => 'async',
            'uiSearch' => true,
        ]);
    }

    /**
     * Configure UI search behavior (static/database selects).
     *
     * - null: auto (enabled when options > threshold, or for non-static modes)
     * - true: always
     * - false: never
     */
    public function uiSearch(?bool $enabled = null, int $threshold = 6): self
    {
        return $this->setConfigs([
            'uiSearch' => $enabled,
            'searchThreshold' => $threshold,
        ]);
    }

    /**
     * Set multiple mode
     */
    public function multiple(bool $multiple = true): self
    {
        return $this->setConfigs([
            'multiple' => $multiple,
            'maxItems' => $multiple ? 100 : 1,
            'removeButton' => $multiple,
        ]);
    }

    /**
     * Set single mode
     */
    public function single(): self
    {
        return $this->multiple(false);
    }

    /**
     * Set positioning mode (dropdown or aligned).
     * Aligned mode highlights the selected option directly in the list
     * instead of using a checkmark indicator.
     */
    public function positioning(string $mode): self
    {
        return $this->setConfig('positioning', $mode);
    }

    /**
     * Shorthand for aligned positioning mode
     */
    public function aligned(): self
    {
        return $this->positioning('aligned');
    }

    /**
     * Set order by
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        return $this->setDatabaseConfig('orderBy', "{$field} {$direction}");
    }

    /**
     * Set limit
     */
    public function limit(int $limit): self
    {
        return $this->setDatabaseConfig('limit', $limit);
    }

    /**
     * Enable/disable clear button
     */
    public function clearButton(bool $enabled = true): self
    {
        return $this->setConfig('clearButton', $enabled);
    }

    /**
     * Enable/disable remove button
     */
    public function removeButton(bool $enabled = true): self
    {
        return $this->setConfig('removeButton', $enabled);
    }

    /**
     * Enable preloading for async mode
     */
    public function preload(bool $enabled = true): self
    {
        return $this->setConfig('preload', $enabled);
    }

    /**
     * Set custom render functions (global JS function names).
     */
    public function setRenders(?string $option = null, ?string $item = null, ?string $noResults = null): self
    {
        return $this->setConfigs([
            'renderOption' => $option,
            'renderItem' => $item,
            'renderNoResults' => $noResults,
        ]);
    }

    /**
     * Set a Blade view for rendering dropdown options.
     * View receives: $item (entity object), $text (display text), $value (option value).
     *
     * Example: ->optionView('admin::partials.select.role-option')
     */
    public function optionView(string $bladeView): self
    {
        return $this->setConfig('optionView', $bladeView);
    }

    /**
     * Set a Blade view for rendering selected items.
     * View receives: $item (entity object), $text (display text), $value (option value).
     *
     * Example: ->itemView('admin::partials.select.role-item')
     */
    public function itemView(string $bladeView): self
    {
        return $this->setConfig('itemView', $bladeView);
    }

    /**
     * Specify extra entity fields to include as option metadata.
     * These fields will be available in data-data JSON on <option> elements
     * and in async search results for custom renderers/views.
     *
     * @param array $fields e.g. ['avatar', 'color', 'icon', 'email']
     */
    public function withMeta(array $fields): self
    {
        return $this->setConfig('extraFields', $fields);
    }

    /**
     * Set maximum items
     */
    public function maxItems(int $max): self
    {
        return $this->setConfig('maxItems', $max);
    }

    /**
     * Add plugin
     */
    public function addPlugin(string $plugin): self
    {
        $plugins = $this->getConfig('plugins', []);
        $plugins[] = $plugin;

        return $this->setConfig('plugins', array_unique($plugins));
    }

    /**
     * Remove plugin
     */
    public function removePlugin(string $plugin): self
    {
        $plugins = $this->getConfig('plugins', []);

        return $this->setConfig('plugins', array_diff($plugins, [$plugin]));
    }

    /**
     * Set configuration option
     */
    public function setConfig(string $key, $value): self
    {
        $this->config[$key] = $value;

        return $this;
    }

    /**
     * Set multiple configuration options
     */
    public function setConfigs(array $configs): self
    {
        foreach ($configs as $key => $value) {
            $this->setConfig($key, $value);
        }

        return $this;
    }

    /**
     * Get configuration option
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set database configuration option
     */
    public function setDatabaseConfig(string $key, $value): self
    {
        $this->databaseConfig[$key] = $value;

        return $this;
    }

    /**
     * Set multiple database configuration options
     */
    public function setDatabaseConfigs(array $configs): self
    {
        foreach ($configs as $key => $value) {
            $this->setDatabaseConfig($key, $value);
        }

        return $this;
    }

    /**
     * Set filter function
     */
    public function filter(callable $callback): self
    {
        return $this->setConfig('filter', $callback);
    }

    public function allowAdd(bool $allow = true): self
    {
        return $this->setConfig('allowAdd', $allow);
    }

    /**
     * Configure select before render
     */
    protected function configureSelect(): void
    {
        // Auto-promote database → async for lazy entities (must run before other config)
        if ($this->getConfig('mode') === 'database') {
            $entity = $this->databaseConfig['entity'];
            if ($entity) {
                $selectRegistry = app(SelectRegistry::class);
                $registryConfig = $selectRegistry->getEntityConfig($entity);
                if (!empty($registryConfig['lazy'])) {
                    $this->setConfig('mode', 'async');
                    $this->setConfig('preload', true);
                    $this->setConfig('uiSearch', true);
                    $this->setConfig('minSearchLength', 0);
                }
            }
        }

        // Set basic attributes
        $this->set('multiple', $this->getConfig('multiple'))->set(
            'maxItems',
            $this->getConfig('multiple') ? $this->getConfig('maxItems', 100) : 1,
        )->set('mode', $this->getConfig('mode'))->set('data-positioning', $this->getConfig('positioning', 'dropdown'));

        // Expose allowAdd flag to template via data attribute
        $this->set('data-allow-add', $this->getConfig('allowAdd') ? 'true' : 'false');

        // Propagate allowEmpty to template
        if ($this->get('allowEmpty') || $this->getConfig('allowEmpty')) {
            $this->set('allowEmpty', true);
        }

        // UI search behavior: true|false|auto
        $uiSearch = $this->getConfig('uiSearch', null);
        $this->set('data-searchable', $uiSearch === null ? 'auto' : ( $uiSearch ? 'true' : 'false' ));
        $this->set('data-search-threshold', (string) $this->getConfig('searchThreshold', 6));

        // Configure plugins
        $plugins = $this->getConfig('plugins', []);
        if ($this->getConfig('clearButton')) {
            $plugins[] = 'clear_button';
        }
        if ($this->getConfig('multiple') && $this->getConfig('removeButton')) {
            $plugins[] = 'remove_button';
        }
        $this->set('data-plugins', json_encode(array_unique($plugins)));

        // Configure options source
        if ($this->getConfig('mode') === 'database') {
            $this->set('options', $this->getDatabaseOptions());
        }

        // Configure async mode
        if ($this->getConfig('mode') === 'async') {
            $this->configureAsyncMode();
        }

        // Pre-load selected items as <option> elements for async mode
        if ($this->getConfig('mode') === 'async') {
            $this->preloadSelectedOptions();
        }

        // Configure render functions
        $this->configureRenderFunctions();

        // Handle value formatting
        $this->formatValue();
    }

    /**
     * Configure async mode settings
     */
    protected function configureAsyncMode(): void
    {
        $this
            ->set('data-search-url', '/admin/select/search')
            ->set('data-search-min-length', $this->getConfig('minSearchLength'))
            ->set('data-search-delay', $this->getConfig('searchDelay'))
            ->set('data-search-fields', json_encode($this->databaseConfig['searchFields']))
            ->set('data-entity', $this->databaseConfig['entity'])
            ->set('data-display-field', $this->databaseConfig['displayField'])
            ->set('data-value-field', $this->databaseConfig['valueField'])
            ->set('data-preload', $this->getConfig('preload') ? 'true' : 'false');

        $extraFields = $this->getConfig('extraFields', []);
        if (!empty($extraFields)) {
            $this->set('data-extra-fields', json_encode($extraFields));
        }

        // Pass custom view overrides for async rendering
        if ($optionView = $this->getConfig('optionView')) {
            $this->set('data-option-view', $optionView);
        }
        if ($itemView = $this->getConfig('itemView')) {
            $this->set('data-item-view', $itemView);
        }
    }

    /**
     * Pre-load selected items as options for async selects
     * so that already-selected values display correctly.
     */
    protected function preloadSelectedOptions(): void
    {
        $value = $this->get('value');
        if (empty($value)) {
            return;
        }

        $entity = $this->databaseConfig['entity'];
        if (!$entity) {
            return;
        }

        $selectRegistry = app(SelectRegistry::class);
        $config = $selectRegistry->getEntityConfig($entity);
        if (!$config) {
            return;
        }

        $valueField = $this->databaseConfig['valueField'] ?? 'id';
        $displayField = $this->databaseConfig['displayField'] ?? 'name';

        $ids = is_array($value) ? $value : [$value];
        $ids = array_filter($ids, static fn($v) => $v !== '' && $v !== null);
        if (empty($ids)) {
            return;
        }

        // Resolve entity IDs to objects
        $repository = orm()->getRepository($config['class']);
        $items = [];
        foreach ($ids as $id) {
            $item = is_object($id) ? $id : $repository->findByPK($id);
            if ($item) {
                $items[] = $item;
            }
        }

        $optionView = $this->getConfig('optionView') ?? $config['optionView'] ?? null;
        $itemView = $this->getConfig('itemView') ?? $config['itemView'] ?? null;
        $extraFields = $this->getConfig('extraFields', []);
        if (empty($extraFields) && !empty($config['extraFields'])) {
            $extraFields = $config['extraFields'];
        }

        $options = [];
        foreach ($items as $item) {
            if (!isset($item->{$valueField}, $item->{$displayField})) {
                continue;
            }
            $key = $item->{$valueField};
            $text = $item->{$displayField};
            $meta = ['text' => $text];

            foreach ($extraFields as $field) {
                if (isset($item->{$field})) {
                    $meta[$field] = $item->{$field};
                }
            }

            try {
                if ($optionView) {
                    $meta['optionHtml'] = view($optionView, [
                        'item' => $item,
                        'text' => $text,
                        'value' => $key,
                    ])->render();
                }
                if ($itemView) {
                    $meta['itemHtml'] = view($itemView, [
                        'item' => $item,
                        'text' => $text,
                        'value' => $key,
                    ])->render();
                }
            } catch (Throwable $e) {
                // View rendering failed — use text-only
            }

            $options[$key] = $meta;
        }

        $this->set('options', $options);
    }

    /**
     * Configure render functions
     */
    protected function configureRenderFunctions(): void
    {
        if ($renderOption = $this->getConfig('renderOption')) {
            $this->set('renderOption', $renderOption);
        }
        if ($renderItem = $this->getConfig('renderItem')) {
            $this->set('renderItem', $renderItem);
        }
        if ($renderNoResults = $this->getConfig('renderNoResults')) {
            $this->set('renderNoResults', $renderNoResults);
        }
    }

    /**
     * Format field value
     */
    protected function formatValue(): void
    {
        $value = $this->get('value');
        if (is_object($value)) {
            $valueField = $this->databaseConfig['valueField'];
            $this->set('value', $value->{$valueField});
        } elseif (is_array($value) || $value instanceof Traversable) {
            $valueField = $this->databaseConfig['valueField'];
            $values = [];
            foreach ($value as $item) {
                $values[] = is_object($item) ? $item->{$valueField} : $item;
            }
            $this->set('value', $values);
        }
    }

    /**
     * Get options from database
     */
    protected function getDatabaseOptions(): array
    {
        $entity = $this->databaseConfig['entity'];
        if (!$entity) {
            return [];
        }

        $selectRegistry = app(SelectRegistry::class);
        $config = $selectRegistry->getEntityConfig($entity);

        if (!$config || !$selectRegistry->canUserAccessAlias($entity)) {
            return [];
        }

        /** @var Repository $repository */
        $repository = orm()->getRepository($config['class']);
        $items = $repository->findAll();

        if ($filter = $this->getConfig('filter')) {
            $items = array_filter($items, $filter);
        }

        $optionView = $this->getConfig('optionView') ?? $config['optionView'] ?? null;
        $itemView = $this->getConfig('itemView') ?? $config['itemView'] ?? null;
        $extraFields = $this->getConfig('extraFields', []);
        if (empty($extraFields) && !empty($config['extraFields'])) {
            $extraFields = $config['extraFields'];
        }

        $hasViews = $optionView || $itemView;
        $hasMeta = !empty($extraFields);

        $options = [];
        foreach ($items as $item) {
            if (!isset($item->{$this->databaseConfig['valueField']}, $item->{$this->databaseConfig['displayField']})) {
                continue;
            }

            $key = $item->{$this->databaseConfig['valueField']};
            $text = $item->{$this->databaseConfig['displayField']};

            if (!$hasViews && !$hasMeta) {
                $options[$key] = $text;

                continue;
            }

            $meta = ['text' => $text];

            foreach ($extraFields as $field) {
                if (isset($item->{$field})) {
                    $meta[$field] = $item->{$field};
                }
            }

            try {
                if ($optionView) {
                    $meta['optionHtml'] = view($optionView, [
                        'item' => $item,
                        'text' => $text,
                        'value' => $key,
                    ])->render();
                }

                if ($itemView) {
                    $meta['itemHtml'] = view($itemView, [
                        'item' => $item,
                        'text' => $text,
                        'value' => $key,
                    ])->render();
                }
            } catch (Throwable $e) {
                // View rendering failed — use text-only
            }

            $options[$key] = $meta;
        }

        return $options;
    }
}
