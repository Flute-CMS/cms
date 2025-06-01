<?php

namespace Flute\Admin\Platform\Actions;

use Flute\Admin\Platform\Action;

class Button extends Action
{
    /**
     * @var string
     */
    protected $view = 'admin::components.button';

    /**
     * Default attributes value.
     *
     * @var array
     */
    protected $attributes = [
        'class' => null,
        'type' => null,
        'yoyo:on' => null,
        'yoyo:ignore' => false,
        'hx-params' => null,
        'novalidate' => false,
        'method' => null,
        'modal' => null,
        'icon' => null,
        'action' => null,
        'confirm' => null,
        'parameters' => [],
        'withLoading' => true,
        'href' => null,
        'isLink' => false,
        'submit' => false,
        'disabled' => false,
        'size' => 'medium',
        'tooltip' => null,
        'baseClasses' => 'btn',
    ];

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    protected $inlineAttributes = [
        'form',
        'formaction',
        'formenctype',
        'formmethod',
        'formnovalidate',
        'formtarget',
        'type',
        'autofocus',
        'disabled',
        'tabindex',
        'hx-params',
        'hx-*'
    ];

    /**
     * Slot content for the button.
     *
     * @var string
     */
    protected $slot = '';

    /**
     * Set the content of the button.
     *
     * @param string $content
     * @return self
     */
    public function slot(string $content) : self
    {
        $this->slot = $content;
        return $this;
    }

    public function baseClasses(string $classes) : self
    {
        return $this->set('baseClasses', $classes);
    }

    /**
     * Set the attributes.
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
     * Disable the form validation.
     *
     * @param bool $novalidate
     * @return self
     */
    public function novalidate(bool $novalidate = true) : self
    {
        return $this->set('formnovalidate', $novalidate ? 'formnovalidate' : null);
    }

    /**
     * Set the action method.
     *
     * @param string $name
     * @param array $parameters
     * @return self
     */
    public function method(string $name, array $parameters = []) : self
    {
        $this->set('yoyo:post', $name);
        if (!empty($parameters)) {
            $this->parameters($parameters);
        }
        return $this;
    }

    public function redirect(string $url, string $target = '_self') : self
    {
        $this->yoyoIgnore(true);
        $this->href($url);
        $this->set('target', $target);
        $this->set('yoyo:on', 'click');
        $this->set('hx-include', 'none');
        $this->set('hx-params', 'not yoyo-id');
        $this->set('swap', true);

        return $this;
    }

    public function yoyoSwap(bool $swap = true) : self
    {
        $this->set('swap', $swap);
        return $this;
    }

    public function yoyoIgnore(bool $ignore = true) : self
    {
        $this->set('yoyo:ignore', $ignore);
        return $this;
    }

    public function modal(string $modalFunc, array $parameters = []) : self
    {
        $encryptedParams = encrypt()->encrypt($parameters);
        $this->set('yoyo:post', "openModal('$modalFunc', '$encryptedParams')");

        return $this;
    }

    /**
     * Set the parameters for the action.
     *
     * @param array|object $parameters
     * @return self
     */
    public function parameters(array|object $parameters) : self
    {
        $parameters = is_array($parameters)
            ? array_filter($parameters, fn($value) => !empty ($value))
            : $parameters;

        return $this->set('yoyo:vals', json_encode($parameters));
    }

    /**
     * Set the icon for the button.
     *
     * @param string $icon
     * @return self
     */
    public function icon(string $icon) : self
    {
        return $this->set('icon', $icon);
    }

    /**
     * Set the action (URL) for the button.
     *
     * @param string $action
     * @return self
     */
    public function action(string $action) : self
    {
        return $this->set('action', $action);
    }

    /**
     * Set the confirmation message.
     *
     * @param string $message
     * @return self
     */
    public function confirm(string $message, string $type = 'error') : self
    {
        return $this->set('confirm', $message)->set('yoyo:on', 'confirmed')->set('confirmType', $type);
    }

    /**
     * Enable or disable the loading state.
     *
     * @param bool $withLoading
     * @return self
     */
    public function withLoading(bool $withLoading = true) : self
    {
        return $this->set('withLoading', $withLoading);
    }

    /**
     * Add CSS classes to the button.
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
     * Enable or disable the button.
     *
     * @param bool $disabled
     * @return self
     */
    public function disabled(bool $disabled = true) : self
    {
        return $this->set('disabled', $disabled);
    }

    /**
     * Set the link for the button, turning it into an <a>.
     *
     * @param string $href
     * @return self
     */
    public function href(string $href) : self
    {
        return $this->set('href', $href)
            ->set('target', '_blank')
            ->yoyoIgnore(true)
            ->set('isLink', true);
    }

    /**
     * Set the size of the button.
     *
     * @param string $size
     * @return self
     */
    public function size(string $size) : self
    {
        return $this->set('size', $size);
    }

    /**
     * Set the tooltip for the button.
     *
     * @param string $tooltip
     * @return self
     */
    public function tooltip(string $tooltip) : self
    {
        return $this->set('tooltip', $tooltip);
    }

    public function fullWidth() : self
    {
        return $this->addClass('w-100');
    }
}
