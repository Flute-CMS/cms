<?php

use Flute\Core\Installer\InstallerFinder;
use Flute\Core\Support\UrlSupport;
use Nette\Utils\Validators;
use Symfony\Component\HttpKernel\Exception\HttpException;
use xPaw\SourceQuery\SourceQuery;

// Spizdil s laravel
if (!function_exists("tap")) {
    function tap($value, $callback)
    {
        $callback($value);

        return $value;
    }
}

if (!function_exists('url')) {
    /**
     * Generate a URL for the given path.
     *
     * @param string $path The path to generate the URL for.
     * @param array $parameters An array of parameters to append to the URL.
     * 
     * @return UrlSupport The generated URL.
     */
    function url(?string $path = null, array $params = [])
    {
        return new UrlSupport($path, $params);
    }
}

if (!function_exists('is_url')) {

    /**
     * Determine if the given string is a valid URL.
     *
     * @param string $value
     * @return bool
     */
    function is_url($value): bool
    {
        return Validators::isUrl($value);
    }
}

if (!function_exists('path')) {
    /**
     * Get the full path with some argument.
     *
     * @param string $path The path
     * 
     * @return string The full path
     */
    function path(string $path = ''): string
    {
        return sprintf('%s/%s', rtrim(BASE_PATH, '/'), ltrim($path, '/'));
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the full path to the given public file.
     *
     * @param string $path The path to the public file.
     * @return string The full path to the public file.
     */
    function public_path(string $path = ''): string
    {
        return sprintf('%s/public/%s', rtrim(BASE_PATH, '/'), ltrim($path, '/'));
    }
}

if (!function_exists('module_path')) {
    /**
     * Get the full path to the given module file.
     *
     * @param string $module The module key.
     * @param string $path The path to the module file.
     * 
     * @return string The full path to the module file.
     */
    function module_path(string $module, string $path = ''): string
    {
        return sprintf('%s/app/Modules/%s/%s', rtrim(BASE_PATH, '/'), $module, ltrim($path, '/'));
    }
}

if (!function_exists('tt')) {
    function tt(string $path = ''): string
    {
        $pathTheme = app()->getTheme() . '/' . ltrim($path, '/');
        return str_contains($pathTheme, 'Themes/') ? $pathTheme : sprintf('Themes/%s', $pathTheme);
    }
}

if (!function_exists('mm')) {
    function mm(string $module, string $path = ''): string
    {
        $pathTheme = $module . '/' . ltrim($path, '/');
        return str_contains($pathTheme, 'Modules/') ? $pathTheme : sprintf('Modules/%s', $pathTheme);
    }
}

if (!function_exists('is_installed')) {
    function is_installed(): bool
    {
        return app(InstallerFinder::class)->isInstalled();
    }
}

if (!function_exists('is_debug')) {
    function is_debug(): bool
    {
        return (bool) app('debug');
    }
}

if (!function_exists('is_performance')) {
    function is_performance(): bool
    {
        return (bool) (app('app.mode') === 'performance');
    }
}

if (!function_exists("abort_if")) {
    function abort_if(bool $condition, int $code = 403, string $message = "")
    {
        if (!$condition)
            throw new HttpException($code, $message);
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

if (!function_exists('tip_active')) {
    function tip_active($key)
    {
        return user()->hasPermission(config("tips_complete.$key.permission"))
            && config("tips_complete.$key")
            && ((bool) config('app.tips')) === true
            && (config("tips_complete.$key.completed")) === false;
    }
}

if (!function_exists('now')) {
    function now()
    {
        return new DateTime();
    }
}

if (!function_exists('old')) {
    function old($value, $default = null)
    {
        return session()->get("__input_$value", $default);
    }
}

if (!function_exists('e')) {
    function e(string $value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        return template()->getBlade()->getCsrfToken();
    }
}

if (!function_exists('table_lang')) {
    function table_lang()
    {
        $locales = [
            'en' => 'en-GB',
            'de' => 'de-DE',
            'fr' => 'fr-FR',
            'es' => 'es-ES'
        ];

        $lang = app()->getLang();

        return isset ($locales[$lang]) ? $locales[$lang] : $lang;
    }
}

if (!function_exists('sq')) {
    function sq(string $ip, int $port, int $timeout = 3, int $engine = 1)
    {
        $Query = new SourceQuery();

        $Query->Connect($ip, $port, $timeout, $engine);

        return $Query;
    }
}