<?php

use Flute\Core\Modules\Translation\Services\TranslationService;
use Symfony\Component\Translation\Translator;

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