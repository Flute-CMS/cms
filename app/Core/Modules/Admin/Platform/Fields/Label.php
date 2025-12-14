<?php

namespace Flute\Admin\Platform\Fields;

use Flute\Admin\Platform\Field;

/**
 * Class Label.
 *
 * @method Label name(string $value = null)
 * @method Label popover(string $value = null)
 * @method Label title(string $value = null)
 */
class Label extends Field
{
    /**
     * @var string
     */
    protected $view = 'admin::partials.fields.label';

    /**
     * Default attributes value.
     *
     * @var array
     */
    protected $attributes = [
        'id' => null,
        'value' => null,
    ];

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    protected $inlineAttributes = [
        'class',
    ];
}
