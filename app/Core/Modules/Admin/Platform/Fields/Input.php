<?php

namespace Flute\Admin\Platform\Fields;

use Flute\Admin\Platform\Concerns\Multipliable;
use Flute\Admin\Platform\Field;

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

            if (is_null($this->get('value')) && !$this->get('disableFromRequest')) {
                $this->set('value', request()->input($this->get('name')));
            }
        });
    }

    /**
     * Sets the input type.
     */
    public function type(string $type): self
    {
        return $this->set('type', $type);
    }

    /**
     * Sets the input name.
     */
    public function name(string $name): self
    {
        return $this->set('name', $name);
    }

    public function disableFromRequest(): self
    {
        return $this->set('disableFromRequest', true);
    }

    /**
     * Sets the input value.
     *
     * @param mixed $value
     */
    public function value($value): self
    {
        return $this->set('value', ($this->get('disableFromRequest') ? $value : request()->input($this->get('name'), $value)));
    }

    /**
     * Sets the prefix text.
     */
    public function prefix(string $prefix): self
    {
        return $this->set('prefix', $prefix);
    }

    /**
     * Sets the input mask.
     *
     * @param mixed $mask
     */
    public function mask($mask): self
    {
        return $this->set('mask', $mask);
    }

    /**
     * Sets the read-only attribute.
     */
    public function readOnly(bool $readOnly = true): self
    {
        return $this->set('readOnly', $readOnly);
    }

    /**
     * Sets the post-prefix content.
     *
     * @param string $postPrefix
     */
    public function postPrefix($postPrefix): self
    {
        return $this->set('postPrefix', $postPrefix);
    }

    /**
     * Enables or disables the toggle functionality (for password fields).
     */
    public function toggle(bool $toggle = true): self
    {
        return $this->set('toggle', $toggle);
    }

    /**
     * Removes the bottom border or margin.
     */
    public function withoutBottom(bool $withoutBottom = true): self
    {
        return $this->set('withoutBottom', $withoutBottom);
    }

    /**
     * Enables or disables FilePond integration for file inputs.
     */
    public function filePond(bool $filePond = true): self
    {
        return $this->set('filePond', $filePond);
    }

    /**
     * Sets options for FilePond.
     */
    public function filePondOptions(array $options): self
    {
        return $this->set('filePondOptions', $options);
    }

    /**
     * Sets the datalist options.
     */
    public function datalist(array $datalist): self
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
     */
    public function defaultFile($defaultFile): self
    {
        return $this->set('defaultFile', $defaultFile);
    }

    /**
     * Sets default files for FilePond file inputs.
     *
     * @param array $files Массив URL файлов
     */
    public function defaultFiles(array $files): self
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
     * @param mixed $value
     */
    public function set(string $key, $value = true): self
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
     * Enables or disables the input.
     */
    public function disabled(bool $disabled = true): self
    {
        return $this->set('disabled', $disabled);
    }

    /**
     * Sets the placeholder text.
     */
    public function placeholder(string $placeholder): self
    {
        return $this->set('placeholder', $placeholder);
    }

    /**
     * Sets the input size.
     */
    public function size(string $size): self
    {
        return $this->set('size', $size);
    }

    /**
     * Sets a tooltip for the input.
     */
    public function tooltip(string $tooltip): self
    {
        return $this->set('tooltip', $tooltip);
    }

    /**
     * Enables multiple file uploads.
     */
    public function multiple(bool $multiple = true): self
    {
        return $this->set('multiple', $multiple);
    }

    /**
     * Устанавливает доступные пакеты иконок для выбора.
     */
    public function iconPacks(array $packs): self
    {
        return $this->set('iconPacks', $packs);
    }
}
