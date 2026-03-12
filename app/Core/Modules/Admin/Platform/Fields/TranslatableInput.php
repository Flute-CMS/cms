<?php

namespace Flute\Admin\Platform\Fields;

use Flute\Admin\Platform\Field;

/**
 * Translatable text input — renders language tabs above a regular input/textarea
 * when more than one language is available. Falls back to a plain input otherwise.
 *
 * Usage in admin screens:
 *   TranslatableInput::make('title')
 *       ->type('text')                  // 'text' (default) or 'textarea'
 *       ->placeholder('Enter title')
 *       ->value($entity->title)         // JSON string or plain string
 */
class TranslatableInput extends Field
{
    protected $view = 'admin::partials.fields.translatable';

    protected $attributes = [
        'class' => 'input-wrapper',
        'type' => 'text',
        'name' => '',
        'value' => null,
        'placeholder' => null,
        'disabled' => false,
        'required' => false,
        'maxlength' => null,
    ];

    protected $inlineAttributes = [
        'autocomplete',
        'autofocus',
        'disabled',
        'maxlength',
        'name',
        'placeholder',
        'readonly',
        'required',
        'tabindex',
        'type',
        'value',
    ];

    /**
     * Language code → flag file mapping for cases where lang code ≠ country code.
     */
    private static array $flagMap = [
        'cs' => 'cz',
        'da' => 'dk',
        'el' => 'gr',
        'et' => 'ee',
        'he' => 'il',
        'hi' => 'in',
        'ko' => 'kr',
        'ms' => 'my',
        'nb' => 'no',
        'nn' => 'no',
        'sl' => 'si',
        'sr' => 'rs',
        'vi' => 'vn',
    ];

    /**
     * Set input type: 'text' or 'textarea'.
     */
    public function type(string $type): self
    {
        return $this->set('type', $type);
    }

    public function placeholder(string $placeholder): self
    {
        return $this->set('placeholder', $placeholder);
    }

    /**
     * Get the list of available languages with metadata for JS.
     */
    public static function getLanguagesData(): array
    {
        $available = config('lang.available', []);
        if (count($available) < 2) {
            return [];
        }

        $result = [];
        foreach ($available as $code) {
            $flagCode = self::$flagMap[$code] ?? $code;
            $result[] = [
                'code' => $code,
                'name' => __('langs.' . $code),
                'flag' => asset('assets/img/langs/' . $flagCode . '.svg'),
            ];
        }

        return $result;
    }
}
