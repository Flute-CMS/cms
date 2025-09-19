<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;

/**
 * Class Field.
 *
 * Represents a form field layout that includes a label, input, and optional small text or right-side content.
 */
class Field extends Layout
{
    /**
     * @var string
     */
    protected $template = 'admin::components.forms.field';

    /**
     * @var array
     */
    protected $layouts = [];

    /**
     * @var array
     */
    protected $attributes = [
        'label' => '',
        'small' => '',
        'right' => '',
        'slot' => null,
        'class' => '',
        'required' => null,
        'popover' => null,
    ];

    public function __construct(
        ?\Flute\Admin\Platform\Field $field = null,
        array $classes = []
    ) {
        if ($field) {
            $this->field($field);
        }

        if (!empty($classes)) {
            $this->addClass($classes);
        }
    }

    /**
     * Sets the label text.
     */
    public function label(string $label): self
    {
        $this->attributes['label'] = $label;

        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->attributes['required'] = $required;

        return $this;
    }

    public function popover(string $popover): self
    {
        $this->attributes['popover'] = $popover;

        return $this;
    }

    /**
     * Sets the small/help text.
     */
    public function small(string $small): self
    {
        $this->attributes['small'] = $small;

        return $this;
    }

    /**
     * Sets the right-side content.
     */
    public function right(string $right): self
    {
        $this->attributes['right'] = $right;

        return $this;
    }

    /**
     * Sets the Input component.
     */
    public function field(\Flute\Admin\Platform\Field $field): self
    {
        $this->attributes['slot'] = $field;

        return $this;
    }

    /**
     * Adds CSS classes to the field.
     *
     * @param string|array $classes
     */
    public function addClass($classes): self
    {
        if (is_array($classes)) {
            $classes = implode(' ', $classes);
        }

        $existing = $this->attributes['class'] ?? '';
        $this->attributes['class'] = trim($existing . ' ' . $classes);

        return $this;
    }

    /**
     * Sets a custom attribute.
     *
     * @param mixed  $value
     */
    public function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Builds the field layout.
     *
     * @return \Illuminate\Contracts\View\View|null
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (!$this->isVisible()) {
            return null;
        }

        if ($this->attributes['slot']) {
            $this->attributes['slot'] = $this->attributes['slot']->build($repository);
        }

        return view($this->template, $this->attributes);
    }
}
