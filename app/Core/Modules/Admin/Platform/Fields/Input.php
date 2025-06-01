<?php

namespace Flute\Admin\Platform\Fields;

use Flute\Admin\Platform\Field;
use Flute\Admin\Platform\Concerns\Multipliable;

class Input extends Field
{
    use Multipliable;

    /**
     * @var string
     */
    protected $view = 'admin::components.fields.input';

    /**
     * Default attributes value.
     *
     * @var array
     */
    protected $attributes = [
        'class' => 'input-wrapper',
        'type' => 'text',
        'name' => '',
        'value' => null,
        'prefix' => '',
        'mask' => '',
        'readOnly' => false,
        'postPrefix' => false,
        'toggle' => true,
        'withoutBottom' => false,
        'filePond' => false,
        'filePondOptions' => [],
        'datalist' => [],
        'defaultFile' => null,
        'disabled' => false,
        'size' => 'medium',
        'placeholder' => null,
        'tooltip' => null,
        'multiple' => false,
        'iconPacks' => [],
    ];

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    protected $inlineAttributes = [
        'accept',
        'accesskey',
        'autocomplete',
        'autofocus',
        'checked',
        'disabled',
        'form',
        'formaction',
        'formenctype',
        'formmethod',
        'formnovalidate',
        'formtarget',
        'list',
        'max',
        'maxlength',
        'min',
        'minlength',
        'name',
        'pattern',
        'placeholder',
        'readonly',
        'required',
        'size',
        'src',
        'step',
        'tabindex',
        'type',
        'value',
        'mask',
        'inputmode',
        'multiple',
    ];

    /**
     * Input constructor.
     */
    public function __construct()
    {
        $this->addBeforeRender(function () {
            $mask = $this->get('mask');

            if (is_array($mask)) {
                $this->set('mask', json_encode($mask));
            }

            if(is_null($this->get('value')) && !$this->get('disableFromRequest')) {
                $this->set('value', request()->input($this->get('name')));
            }
        });
    }

    /**
     * Sets the input type.
     *
     * @param string $type
     * @return self
     */
    public function type(string $type) : self
    {
        return $this->set('type', $type);
    }

    /**
     * Sets the input name.
     *
     * @param string $name
     * @return self
     */
    public function name(string $name) : self
    {
        return $this->set('name', $name);
    }

    public function disableFromRequest() : self
    {
        return $this->set('disableFromRequest', true);
    }

    /**
     * Sets the input value.
     *
     * @param mixed $value
     * @return self
     */
    public function value($value) : self
    {
        return $this->set('value', ($this->get('disableFromRequest') ? $value : request()->input($this->get('name'), $value)));
    }

    /**
     * Sets the prefix text.
     *
     * @param string $prefix
     * @return self
     */
    public function prefix(string $prefix) : self
    {
        return $this->set('prefix', $prefix);
    }

    /**
     * Sets the input mask.
     *
     * @param mixed $mask
     * @return self
     */
    public function mask($mask) : self
    {
        return $this->set('mask', $mask);
    }

    /**
     * Sets the read-only attribute.
     *
     * @param bool $readOnly
     * @return self
     */
    public function readOnly(bool $readOnly = true) : self
    {
        return $this->set('readOnly', $readOnly);
    }

    /**
     * Sets the post-prefix content.
     *
     * @param string $postPrefix
     * @return self
     */
    public function postPrefix($postPrefix) : self
    {
        return $this->set('postPrefix', $postPrefix);
    }

    /**
     * Enables or disables the toggle functionality (for password fields).
     *
     * @param bool $toggle
     * @return self
     */
    public function toggle(bool $toggle = true) : self
    {
        return $this->set('toggle', $toggle);
    }

    /**
     * Removes the bottom border or margin.
     *
     * @param bool $withoutBottom
     * @return self
     */
    public function withoutBottom(bool $withoutBottom = true) : self
    {
        return $this->set('withoutBottom', $withoutBottom);
    }

    /**
     * Enables or disables FilePond integration for file inputs.
     *
     * @param bool $filePond
     * @return self
     */
    public function filePond(bool $filePond = true) : self
    {
        return $this->set('filePond', $filePond);
    }

    /**
     * Sets options for FilePond.
     *
     * @param array $options
     * @return self
     */
    public function filePondOptions(array $options) : self
    {
        return $this->set('filePondOptions', $options);
    }

    /**
     * Sets the datalist options.
     *
     * @param array $datalist
     * @return self
     */
    public function datalist(array $datalist) : self
    {
        if (!empty($datalist)) {
            $this->set('datalist', $datalist);
            $this->set('list', 'datalist-' . $this->get('name'));
        }
        return $this;
    }

    /**
     * Sets the default file for file inputs.
     *
     * @param mixed $defaultFile
     * @return self
     */
    public function defaultFile($defaultFile) : self
    {
        return $this->set('defaultFile', $defaultFile);
    }

    /**
     * Sets default files for FilePond file inputs.
     *
     * @param array $files Массив URL файлов
     * @return self
     */
    public function defaultFiles(array $files) : self
    {
        $items = [];
        foreach ($files as $file) {
            $items[] = ['source' => $file];
        }
        $options = $this->get('filePondOptions') ?? [];
        $options['files'] = $items;
        return $this->filePondOptions($options);
    }

    /**
     * Sets a custom attribute.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, $value = true) : self
    {
        if (in_array($key, $this->inlineAttributes)) {
            $this->attributes[$key] = $value;
        } else {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Adds CSS classes to the input.
     *
     * @param string|array $classes
     * @return self
     */
    public function addClass($classes) : self
    {
        if (is_array($classes)) {
            $classes = implode(' ', $classes);
        }

        $existing = $this->attributes['class'] ?? '';
        $this->attributes['class'] = trim($existing . ' ' . $classes);

        return $this;
    }

    /**
     * Enables or disables the input.
     *
     * @param bool $disabled
     * @return self
     */
    public function disabled(bool $disabled = true) : self
    {
        return $this->set('disabled', $disabled);
    }

    /**
     * Sets the placeholder text.
     *
     * @param string $placeholder
     * @return self
     */
    public function placeholder(string $placeholder) : self
    {
        return $this->set('placeholder', $placeholder);
    }

    /**
     * Sets the input size.
     *
     * @param string $size
     * @return self
     */
    public function size(string $size) : self
    {
        return $this->set('size', $size);
    }

    /**
     * Sets a tooltip for the input.
     *
     * @param string $tooltip
     * @return self
     */
    public function tooltip(string $tooltip) : self
    {
        return $this->set('tooltip', $tooltip);
    }

    /**
     * Enables multiple file uploads.
     *
     * @param bool $multiple
     * @return self
     */
    public function multiple(bool $multiple = true) : self
    {
        return $this->set('multiple', $multiple);
    }

    /**
     * Устанавливает доступные пакеты иконок для выбора.
     *
     * @param array $packs
     * @return self
     */
    public function iconPacks(array $packs) : self
    {
        return $this->set('iconPacks', $packs);
    }
}
