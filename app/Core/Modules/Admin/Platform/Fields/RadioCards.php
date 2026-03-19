<?php

namespace Flute\Admin\Platform\Fields;

use Flute\Admin\Platform\Field;

/**
 * Class RadioCards.
 *
 * A minimalist grid-based radio selector with icon cards.
 * Perfect for visual choices like layout type, visibility, position, etc.
 *
 * @method RadioCards accesskey($value = true)
 * @method RadioCards autofocus($value = true)
 * @method RadioCards disabled(bool $value = true)
 * @method RadioCards form(string $value)
 * @method RadioCards name(string $value = null)
 * @method RadioCards required(bool $value = true)
 * @method RadioCards tabindex($value = true)
 * @method RadioCards help(string $value = null)
 * @method RadioCards title(string $value = null)
 */
class RadioCards extends Field
{
    /**
     * @var string
     */
    protected $view = 'admin::partials.fields.radiocards';

    /**
     * Default attributes value.
     *
     * @var array
     */
    protected $attributes = [
        'class' => 'radio-cards',
        'value' => null,
        'options' => [],
        'columns' => 3,
        'size' => 'medium',
        'yoyo' => false,
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
        'required',
        'tabindex',
    ];

    /**
     * Set the options for the radio cards.
     *
     * Each option can be:
     * - Simple: ['value' => 'Label']
     * - With icon: ['value' => ['label' => 'Label', 'icon' => 'ph.bold.icon-name']]
     * - With description: ['value' => ['label' => 'Label', 'desc' => 'Description']]
     * - Full: ['value' => ['label' => 'Label', 'icon' => 'ph.bold.icon-name', 'desc' => 'Desc']]
     *
     * @return $this
     */
    public function options(array $options): self
    {
        $normalizedOptions = [];

        foreach ($options as $value => $option) {
            if (is_string($option)) {
                $normalizedOptions[$value] = [
                    'label' => $option,
                    'icon' => null,
                    'desc' => null,
                ];
            } elseif (is_array($option)) {
                $normalizedOptions[$value] = [
                    'label' => $option['label'] ?? null,
                    'icon' => $option['icon'] ?? null,
                    'desc' => $option['desc'] ?? null,
                ];
            }
        }

        return $this->set('options', $normalizedOptions);
    }

    /**
     * Set the number of grid columns.
     *
     * @return $this
     */
    public function columns(int $columns): self
    {
        return $this->set('columns', max(1, min(6, $columns)));
    }

    /**
     * Set size: 'small', 'medium', 'large'
     *
     * @return $this
     */
    public function size(string $size): self
    {
        return $this->set('size', $size);
    }

    /**
     * @return $this
     */
    public function value(mixed $value): self
    {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        return $this->set('value', (string) $value);
    }

    /**
     * Enable yoyo/htmx integration.
     *
     * @return $this
     */
    public function yoyo(): self
    {
        return $this->set('yoyo', true);
    }
}
