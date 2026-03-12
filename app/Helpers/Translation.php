<?php

use Flute\Core\Modules\Translation\Services\TranslationService;

if (!function_exists('__')) {
    /**
     * Get the translation for a given key.
     *
     * @param  string  $key
     * @param  array|string  $replacements
     * @param  string|null  $locale
     * @return string
     */
    function __(string $key, array|string $replacements = [], string $locale = null) : string
    {
        static $translator = null;
        
        if ($translator === null) {
            $translator = translation();
        }

        if (is_string($replacements)) {
            $default = $replacements;
            $replacements = [];
        }

        $trans =  $translator->trans($key, $replacements, $locale);

        if($trans === $key && isset($default)) {
            return $default;
        }

        return $trans;
    }
}

if (!function_exists("t")) {
    /**
     * Get the translation for a given key.
     *
     * @param  string  $key
     * @param  array|string  $replacements
     * @param  string|null  $locale
     * @return string
     */
    function t(string $key, array|string $replacements = [], string $locale = null) : string
    {
        return __($key, $replacements, $locale);
    }
}

if (!function_exists("trans")) {
    /**
     * Get the translation for a given key.
     * Alias of __ function.
     *
     * @param  string  $key
     * @param  array  $replacements
     * @param  string|null  $locale
     * @return string
     */
    function trans(string $key, array $replacements = [], string $locale = null) : string
    {
        return __($key, $replacements, $locale);
    }
}

if (!function_exists("transValue")) {
    /**
     * Resolve a translatable value (JSON or plain string) for the current locale.
     *
     * Accepts either a plain string (returned as-is for backward compat)
     * or a JSON-encoded object / PHP array keyed by locale codes, e.g.
     *   {"ru":"Главная","en":"Home"}
     *
     * Fallback chain: requested locale → site default locale → first available → ''
     *
     * @param  mixed        $value   Raw DB value (string, array, or null)
     * @param  string|null  $locale  Override locale (null = current)
     * @return string
     */
    function transValue(mixed $value, ?string $locale = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Plain string that is NOT JSON
        if (is_string($value)) {
            if ($value[0] !== '{') {
                // If looks like a translation key (e.g. "def.home"), try __() for backward compat
                if (str_contains($value, '.') && !str_contains($value, ' ')) {
                    $translated = __($value, [], $locale);
                    if ($translated !== $value) {
                        return $translated;
                    }
                }
                return $value;
            }

            $decoded = json_decode($value, true);

            if (!is_array($decoded)) {
                return $value;
            }

            $value = $decoded;
        }

        if (!is_array($value)) {
            return (string) $value;
        }

        // Empty array
        if (empty($value)) {
            return '';
        }

        $locale ??= app()->getLang();
        $defaultLocale = config('lang.locale', 'en');

        return $value[$locale]
            ?? $value[$defaultLocale]
            ?? reset($value)
            ?: '';
    }
}

if (!function_exists("translation")) {
    /**
     * Get the translation service instance.
     * 
     * @return TranslationService
     */
    function translation() : TranslationService
    {
        static $service = null;
        
        if ($service === null) {
            $service = app(TranslationService::class);
        }
        
        return $service;
    }
}