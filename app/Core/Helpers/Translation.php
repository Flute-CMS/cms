<?php

use Flute\Core\Services\LanguageService;
use Symfony\Component\Translation\Translator;

if (!function_exists('__')) {
    /**
     * Get the translation for a given key.
     *
     * @param  string  $key
     * @param  array  $replacements
     * @param  string|null  $locale
     * @return string
     */
    function __(string $key, array $replacements = [], string $locale = null): string
    {
        $translator = translation();

        // Если локаль не указана, используйте локаль по умолчанию
        if ($locale === null) {
            $locale = $translator->getLocale();
        }

        // Если у нас нет точки, т.е. массива, то мы не разделяем
        if( strpos( $key, '.' ) !== false ) {
            // Разделяем ключ на домен и ключ перевода
            list($domain, $translationKey) = explode('.', $key, 2);

            return $translator->trans($translationKey, $replacements, $domain, $locale);
        }

        return $translator->trans($key, $replacements, null, $locale);
    }
}

if (!function_exists("t")) {
    /**
     * Get the translation for a given key.
     *
     * @param  string  $key
     * @param  array  $replacements
     * @param  string|null  $locale
     * @return string
     */
    function t(string $key, array $replacements = [], string $locale = null): string
    {
        return __($key, $replacements, $locale);
    }
}

if (!function_exists("translation")) {
    function translation(): Translator
    {
        return app(LanguageService::class)->getTranslator();
    }
}

if (!function_exists("translation_service")) {
    function translation_service(): LanguageService
    {
        return app()->get(LanguageService::class);
    }
}