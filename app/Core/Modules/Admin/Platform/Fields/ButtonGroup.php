<?php

namespace Flute\Admin\Platform\Fields;

use Flute\Admin\Platform\Field;

/**
 * Class ButtonGroup.
 *
 * A beautiful segmented control / button group field with support for icons, text and tooltips.
 * Can be used as a replacement for select or toggle fields.
 *
 * @method ButtonGroup accesskey($value = true)
 * @method ButtonGroup autofocus($value = true)
 * @method ButtonGroup disabled(bool $value = true)
 * @method ButtonGroup form(string $value)
 * @method ButtonGroup name(string $value = null)
 * @method ButtonGroup required(bool $value = true)
 * @method ButtonGroup tabindex($value = true)
 * @method ButtonGroup help(string $value = null)
 * @method ButtonGroup title(string $value = null)
 * @method ButtonGroup size(string $value = 'medium')
 * @method ButtonGroup color(string $value = 'primary')
 */
class ButtonGroup extends Field
{
    /**
     * @var string
     */
    protected $view = 'admin::partials.fields.buttongroup';

    /**
     * Default attributes value.
     *
     * @var array
     */
    protected $attributes = [
        'class' => 'button-group',
        'value' => null,
        'options' => [],
        'size' => 'medium',
        'color' => 'primary',
        'fullWidth' => false,
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
     * Set the options for the button group.
     *
     * Each option can be:
     * - Simple: ['value' => 'Label']
     * - With icon: ['value' => ['label' => 'Label', 'icon' => 'ph.bold.icon-name']]
     * - With tooltip: ['value' => ['label' => 'Label', 'tooltip' => 'Tooltip text']]
     * - Icon only: ['value' => ['icon' => 'ph.bold.icon-name', 'tooltip' => 'Tooltip']]
     * - Full: ['value' => ['label' => 'Label', 'icon' => 'ph.bold.icon-name', 'tooltip' => 'Tooltip']]
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
                    'tooltip' => null,
                ];
            } elseif (is_array($option)) {
                $normalizedOptions[$value] = [
                    'label' => $option['label'] ?? null,
                    'icon' => $option['icon'] ?? null,
                    'tooltip' => $option['tooltip'] ?? null,
                ];
            }
        }

        return $this->set('options', $normalizedOptions);
    }

    /**
     * Set boolean options for toggle-like behavior.
     *
     * @return $this
     */
    public function boolean(
        string $yesLabel = '',
        string $noLabel = '',
        ?string $yesIcon = 'ph.bold.check-bold',
        ?string $noIcon = 'ph.bold.x-bold'
    ): self {
        return $this->options([
            '1' => [
                'label' => $yesLabel ?: __('def.yes'),
                'icon' => $yesIcon,
            ],
            '0' => [
                'label' => $noLabel ?: __('def.no'),
                'icon' => $noIcon,
            ],
        ]);
    }

    /**
     * Set size: 'tiny', 'small', 'medium', 'large'
     *
     * @return $this
     */
    public function size(string $size): self
    {
        return $this->set('size', $size);
    }

    /**
     * Set color theme: 'primary', 'accent', 'secondary'
     *
     * @return $this
     */
    public function color(string $color): self
    {
        return $this->set('color', $color);
    }

    /**
     * Make the button group take full width.
     *
     * @return $this
     */
    public function fullWidth(bool $fullWidth = true): self
    {
        return $this->set('fullWidth', $fullWidth);
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

    /**
     * @return $this
     */
    public function value(mixed $value): self
    {
        // Convert boolean to string for comparison
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        return $this->set('value', (string) $value);
    }
}
