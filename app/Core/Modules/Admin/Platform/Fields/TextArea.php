<?php

namespace Flute\Admin\Platform\Fields;

use Flute\Admin\Platform\Field;

/**
 * Class TextArea.
 *
 * @method TextArea accesskey($value = true)
 * @method TextArea autofocus($value = true)
 * @method TextArea cols($value = true)
 * @method TextArea disabled($value = true)
 * @method TextArea form($value = true)
 * @method TextArea maxlength(int $value)
 * @method TextArea name(string $value = null)
 * @method TextArea placeholder(string $value = null)
 * @method TextArea readonly($value = true)
 * @method TextArea required(bool $value = true)
 * @method TextArea rows(int $value)
 * @method TextArea tabindex($value = true)
 * @method TextArea wrap($value = true)
 * @method TextArea help(string $value = null)
 * @method TextArea max(int $value)
 * @method TextArea popover(string $value = null)
 * @method TextArea title(string $value = null)
 * @method TextArea label(string $value = null)
 * @method TextArea readOnly(bool $value = false)
 * @method TextArea withoutBottom(bool $value = false)
 * @method TextArea additionalClass(string $value)
 */
class TextArea extends Field
{
    /**
     * @var string
     */
    protected $view = 'admin::partials.fields.textarea';

    /**
     * Default attributes value.
     *
     * @var array
     */
    protected $attributes = [
        'class' => 'form-control no-resize',
        'value' => null,
    ];

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    protected $inlineAttributes = [
        'accesskey',
        'autofocus',
        'cols',
        'disabled',
        'form',
        'maxlength',
        'name',
        'placeholder',
        'readonly',
        'required',
        'rows',
        'tabindex',
        'wrap',
        'readOnly',
        'withoutBottom',
        'additional-class',
    ];

    /**
     * Устанавливает текст метки для текстовой области.
     *
     * @return $this
     */
    public function label(string $value)
    {
        $this->set('label', $value);

        return $this;
    }

    /**
     * Устанавливает состояние только для чтения.
     *
     * @return $this
     */
    public function readOnly(bool $value = false)
    {
        $this->set('readOnly', $value);

        return $this;
    }

    /**
     * Устанавливает, нужно ли отображать без нижнего отступа.
     *
     * @return $this
     */
    public function withoutBottom(bool $value = false)
    {
        $this->set('withoutBottom', $value);

        return $this;
    }

    /**
     * Добавляет дополнительные классы к элементу.
     *
     * @return $this
     */
    public function additionalClass(string $value)
    {
        $this->attributes['class'] .= ' ' . $value;

        return $this;
    }
}
