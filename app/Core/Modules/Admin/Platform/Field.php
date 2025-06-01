<?php

namespace Flute\Admin\Platform;

use Closure;
use Flute\Admin\Platform\Contracts\Fieldable;
use Flute\Admin\Platform\Exceptions\FieldRequiredAttributeException;
use Flute\Admin\Platform\Traits\IsVisible;
use Flute\Core\Traits\Makeable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\View;
use Throwable;

class Field implements Fieldable, Htmlable
{
    use IsVisible, Conditionable, Macroable, Makeable {
        Macroable::__call as macroCall;
    }

    /**
     * A set of closure functions
     * that must be executed before data is displayed.
     *
     * @var Closure[]
     */
    private $beforeRender = [];

    /**
     * View template show.
     *
     * @var string
     */
    protected $view;

    /**
     * An array containing all attributes available to the field.
     * Attributes are used to configure the HTML form element.
     *
     * @var array
     */
    protected $attributes = [
        'value' => null,
        'yoyo' => false,
    ];

    /**
     * Required Attributes.
     *
     * @var array
     */
    protected $required = [
        'name',
    ];

    /**
     * Vertical or Horizontal
     * bootstrap forms.
     *
     * @var Closure|string|null
     */
    protected $typeForm;

    /**
     * A set of attributes for the assignment
     * of which will automatically translate them.
     *
     * @var array
     */
    protected $translations = [
        'title',
        'placeholder',
        'help',
    ];

    /**
     * Universal attributes are applied to almost all tags,
     * so they are allocated to a separate group so that they do not repeat for all tags.
     *
     * @var array
     */
    protected $universalAttributes = [
        'accesskey',
        'class',
        'dir',
        'hidden',
        'id',
        'lang',
        'spellcheck',
        'style',
        'tabindex',
        'title',
        'xml:lang',
        'autocomplete',
        'data-*',
        'aria-*',
        'yoyo:*',
        'yoyo',
        'target',
    ];

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    protected $inlineAttributes = [];

    /**
     * @param string $method
     * @param array  $parameters
     *
     * @return $this|mixed|static
     */
    public function __call(string $method, array $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        $arguments = collect($parameters)->map(static fn ($argument) => $argument instanceof Closure ? $argument() : $argument);

        if (method_exists($this, $method)) {
            $this->$method($arguments);
        }

        return $this->set($method, $arguments->first() ?? true);
    }

    /**
     * Sets the 'value' attribute of the field.
     *
     * @param mixed $value The value to be set for the 'value' attribute.
     */
    public function value(mixed $value) : self
    {
        return $this->set('value', $value);
    }

    /**
     * Sets the value for the specified attribute of the field.
     *
     * @param string $key   The name of the attribute to set.
     * @param mixed  $value The value of the attribute. Defaults to true.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function set(string $key, $value = true) : self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Validates that all required attributes are present in the field.
     *
     * @throws FieldRequiredAttributeException if any required attribute is missing.
     *
     * @return static Returns the current instance for method chaining.
     */
    protected function ensureRequiredAttributesArePresent() : self
    {
        collect($this->required)
            ->filter(fn ($attribute) => ! array_key_exists($attribute, $this->attributes))
            ->each(function ($attribute) {
                throw new FieldRequiredAttributeException($attribute);
            });

        return $this;
    }

    /**
     * Renders the field.
     *
     * @throws Throwable
     *
     * @return Factory|View|mixed|void
     */
    public function render()
    {
        if (! $this->isVisible()) {
            return;
        }

        $this
            ->ensureRequiredAttributesArePresent()
            ->customizeFieldName()
            ->updateFieldValue()
            ->runBeforeRender()
            ->translateAttributes()
            ->markFieldWithError()
            ->generateId();

        return view($this->view, array_merge($this->getAttributes(), [
            'attributes' => $this->getAllowAttributes(),
            'dataAttributes' => $this->getAllowDataAttributes(),
            'typeForm' => $this->typeForm ?? $this->vertical()->typeForm,
        ]));
    }

    /**
     * Translates the field's attributes if necessary.
     *
     * @return static
     */
    private function translateAttributes() : self
    {
        $lang = $this->get('lang');

        collect($this->attributes)
            ->intersectByKeys(array_flip($this->translations))
            ->each(function ($value, $key) use ($lang) {
                $this->set($key, is_string($value) ? __($value, [], $lang) : $value);
            });

        return $this;
    }

    /**
     * Gets the field's attributes.
     *
     * @return array
     */
    public function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * Gets the allowed attributes for the field.
     *
     * @return ComponentAttributeBag
     */
    protected function getAllowAttributes() : ComponentAttributeBag
    {
        $allow = array_merge($this->universalAttributes, $this->inlineAttributes);

        $attributes = collect($this->getAttributes())
            ->filter(fn ($value, $attribute) => Str::is($allow, $attribute))
            ->toArray();

        return (new ComponentAttributeBag())
            ->merge($attributes);
    }

    /**
     * Gets the allowed data attributes for the field.
     *
     * @return ComponentAttributeBag
     */
    protected function getAllowDataAttributes() : ComponentAttributeBag
    {
        return $this->getAllowAttributes()->filter(fn ($value, $key) => Str::startsWith($key, 'data-'));
    }

    /**
     * Generates a field ID if not already set.
     *
     * @param string $defaultId The default ID to set if none is provided.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function generateId() : self
    {
        if (! empty($this->get('id'))) {
            return $this;
        }

        $slug = collect([
            'field',
            $this->get('lang'),
            $this->get('name'),
            sha1(json_encode($this->getAttributes())),
        ])->implode('-');

        return $this->set('id', Str::slug($slug));
    }

    /**
     * @param mixed|null $value
     *
     * @return static|mixed|null
     */
    public function get(string $key, $value = null)
    {
        return $this->attributes[$key] ?? $value;
    }

    /**
     * Modifies the 'name' attribute of the field by adding a prefix and/or language identifier if they are set.
     *
     * If both prefix and language are set, the name will be modified as "prefix[lang]name".
     * If only the prefix is set, the name will be modified as "prefixname".
     * If only the language is set, the name will be modified as "lang[name]".
     *
     * @return static Returns the current instance for method chaining.
     */
    protected function customizeFieldName() : self
    {
        $name = $this->get('name');
        $prefix = $this->get('prefix');
        $lang = $this->get('lang');

        if ($prefix !== null && $lang !== null) {
            return $this->set('name', $prefix.'['.$lang.']'.$name);
        }

        if ($prefix !== null) {
            return $this->set('name', $prefix.$name);
        }

        if ($lang !== null) {
            return $this->set('name', $lang.'['.$name.']');
        }

        return $this;
    }

    /**
     * Modifies the 'value' attribute of the field.
     *
     * Retrieves the old value using the getOldValue() method, falling back to the current 'value' attribute if no old value is found.
     * If the value is a Closure, it will be executed with the current attributes and its result will be used as the value.
     *
     * @return static Returns the current instance for method chaining.
     */
    protected function updateFieldValue() : self
    {
        $value = $this->get('value');

        if ($value instanceof Closure) {
            $value = $value($this->attributes);
        }

        return $this->set('value', $value);
    }

    /**
     * Use vertical layout for the field.
     *
     * @return static
     */
    public function vertical() : self
    {
        $this->typeForm = 'admin::partials.fields.vertical';

        return $this;
    }

    /**
     * Use clear layout for the field.
     *
     * @return static
     */
    public function clear() : self
    {
        $this->typeForm = 'admin::partials.fields.clear';

        return $this;
    }

    /**
     * Use horizontal layout for the field.
     *
     * @return static
     */
    public function horizontal() : self
    {
        $this->typeForm = 'admin::partials.fields.horizontal';

        return $this;
    }

    /**
     * Displaying an item without titles or additional information.
     *
     * @return $this
     */
    public function withoutFormType() : self
    {
        $this->typeForm = static fn (array $attributes) => $attributes['slot'];

        return $this;
    }

    /**
     * Create separate line after the field.
     *
     * @return static
     */
    public function hr() : self
    {
        $this->set('hr');

        return $this;
    }

    /**
     * Adds a closure to be executed before rendering the field.
     *
     * @param Closure $closure The closure to be executed before rendering.
     *
     * @return static
     */
    public function addBeforeRender(Closure $closure)
    {
        $this->beforeRender[] = $closure;

        return $this;
    }

    /**
     * Performs all tasks added to be executed before rendering the field.
     *
     * This method iterates over each closure added via addBeforeRender() method
     * and executes them in the context of the current field instance.
     */
    public function runBeforeRender() : self
    {
        foreach ($this->beforeRender as $before) {
            $before->call($this);
        }

        return $this;
    }

    public function popover(string $text) : self
    {
        $this->set('popover', $text);

        return $this;
    }

    public function yoyo()
    {
        $this->set('yoyo', true);

        return $this;
    }

    /**
     * Converts the field to a string by rendering it.
     *
     * @throws Throwable
     *
     * @return string
     */
    public function __toString() : string
    {
        $view = $this->render();

        if (is_string($view)) {
            return $view;
        }

        if (is_a($view, View::class)) {
            return (string) $view->render();
        }

        return '';
    }

    /**
     * Converts the field to an HTML string.
     *
     * @throws Throwable
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->render()->toHtml();
    }
}
