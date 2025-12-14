<?php

namespace Flute\Admin\Platform\Fields;

use Flute\Admin\Platform\Field;

/**
 * Class Toggle.
 *
 * @method Toggle accesskey($value = true)
 * @method Toggle autofocus($value = true)
 * @method Toggle disabled(bool $value = true)
 * @method Toggle form(string $value)
 * @method Toggle name(string $value = null)
 * @method Toggle placeholder(string $value = null)
 * @method Toggle readonly(bool $value = true)
 * @method Toggle required(bool $value = true)
 * @method Toggle tabindex($value = true)
 * @method Toggle label(string $value = null)
 * @method Toggle checked(bool $value = false)
 * @method Toggle sendTrueOrFalse(bool $value = false)
 * @method Toggle yesvalue(string $value = '1')
 * @method Toggle novalue(string $value = '0')
 * @method Toggle withoutBottom(bool $value = false)
 * @method Toggle additionalClass(string $value)
 */
class Toggle extends Field
{
    /**
     * @var string
     */
    protected $view = 'admin::partials.fields.toggle';

    /**
     * Default attributes value.
     *
     * @var array
     */
    protected $attributes = [
        'class' => 'toggle-switch-input',
        'value' => '1',
        'checked' => false,
        'disabled' => false,
        'sendTrueOrFalse' => false,
        'yesvalue' => '1',
        'novalue' => '0',
        'label' => null,
    ];

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    protected $inlineAttributes = [
        'accesskey',
        'autofocus',
        'disabled',
        'form',
        'name',
        'placeholder',
        'readonly',
        'required',
        'tabindex',
        'label',
        'checked',
        'sendTrueOrFalse',
        'yesvalue',
        'novalue',
        'withoutBottom',
        'additional-class',
    ];

    public function value(mixed $value): Field
    {
        $this->checked($value);

        return $this;
    }

    /**
     * Устанавливает текст метки для переключателя.
     *
     * @return $this
     */
    public function label(string $value)
    {
        $this->set('label', $value);

        return $this;
    }

    /**
     * Устанавливает состояние переключателя.
     *
     * @return $this
     */
    public function checked(bool $value = false)
    {
        $this->set('checked', $value);

        return $this;
    }

    /**
     * Устанавливает, нужно ли отправлять true/false значения.
     *
     * @return $this
     */
    public function sendTrueOrFalse(bool $value = false)
    {
        $this->set('sendTrueOrFalse', $value);

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

    /**
     * Настраивает toggle для работы с YOYO/HTMX
     *
     * @return $this
     */
    public function yoyo()
    {
        $this->set('yoyo', true);
        $this->set('hx-trigger', 'change');

        return $this;
    }
}
