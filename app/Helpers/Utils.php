<?php

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Flute\Core\Modules\Installer\Services\InstallerConfig;
use Flute\Core\Services\CsrfTokenService;
use Flute\Core\Support\UrlSupport;
use Flute\Core\Validator\FluteValidator;
use Flute\Core\Markdown\Parser;
use Nette\Utils\Validators;
use Symfony\Component\HttpKernel\Exception\HttpException;
use xPaw\SourceQuery\SourceQuery;

// Spizdil s laravel
if (! function_exists("tap")) {
    function tap($value, $callback)
    {
        $callback($value);

        return $value;
    }
}

if (! function_exists('url')) {
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

if (! function_exists('is_url')) {

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

if (! function_exists('path')) {
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

// CMS - Core Modules Service
if (! function_exists('cms_path')) {
    function cms_path(string $path = '', bool $fullPath = false): string
    {
        return $fullPath ? path('app/Core/Modules/' . $path) : 'app/Core/Modules/' . $path;
    }
}

if (! function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return sprintf('%s/storage/%s', rtrim(BASE_PATH, '/'), ltrim($path, '/'));
    }
}

if (! function_exists('public_path')) {
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

if (! function_exists('module_path')) {
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

if (! function_exists('tt')) {
    function tt(string $path = ''): string
    {
        $pathTheme = str_replace('.blade.php', '', app()->getTheme() . '/' . ltrim($path, '/'));
        return str_contains($pathTheme, 'app/Themes/') ? $pathTheme : sprintf('Themes/%s', $pathTheme);
    }
}

if (! function_exists('mm')) {
    function mm(string $module, string $path = ''): string
    {
        $pathTheme = $module . '/' . ltrim($path, '/');
        return str_contains($pathTheme, 'app/Modules/') ? $pathTheme : sprintf('Modules/%s', $pathTheme);
    }
}

if (! function_exists('asset')) {
    function asset($path)
    {
        return template()->getAsset($path);
    }
}

if (! function_exists('is_installed')) {
    function is_installed(): bool
    {
        return app(InstallerConfig::class)->isInstalled();
    }
}

if (! function_exists('is_debug')) {
    function is_debug(): bool
    {
        if (is_cli())
            return true;

        if (is_development())
            return true;

        $debug = (bool) app('debug');
        $user_ip = request()->ip();

        if (in_array($user_ip, config('app.debug_ips'))) {
            return true;
        }

        return $debug;
    }
}


if (! function_exists('is_development')) {
    function is_development(): bool
    {
        return filter_var(config('app.development_mode'), FILTER_VALIDATE_BOOLEAN) === true;
    }
}


if (! function_exists('is_performance')) {
    function is_performance(): bool
    {
        if (is_debug() || is_development())
            return false;

        return (filter_var(config('app.is_performance'), FILTER_VALIDATE_BOOLEAN) === true);
    }
}

if (! function_exists('is_admin_path')) {
    function is_admin_path(): bool
    {
        return str_starts_with(request()->getPathInfo(), '/admin');
    }
}

if (! function_exists("abort_if")) {
    function abort_if(bool $condition, int $code = 403, string $message = "")
    {
        if (! $condition)
            throw new HttpException($code, $message);
    }
}

if (! function_exists('getallheaders')) {
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

if (! function_exists('tip_active')) {
    function tip_active($key)
    {
        return user()->can(config("tips_complete.$key.permission"))
            && config("tips_complete.$key")
            && ((bool) config('app.tips')) === true
            && (config("tips_complete.$key.completed")) === false;
    }
}

if (! function_exists('now')) {
    function now()
    {
        return new DateTimeImmutable();
    }
}

if (! function_exists('e')) {
    function e(string $value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (! function_exists('csrf_token')) {
    function csrf_token()
    {
        return app(CsrfTokenService::class)->getToken();
    }
}

if (! function_exists('csrf_field')) {
    function csrf_field()
    {
        echo "<input name='x-csrf-token' type='hidden' value='" . csrf_token() . "' />";
    }
}

if (! function_exists('table_lang')) {
    function table_lang()
    {
        $locales = [
            'en' => 'en-GB',
            'de' => 'de-DE',
            'fr' => 'fr-FR',
            'es' => 'es-ES'
        ];

        $lang = app()->getLang();

        return isset($locales[$lang]) ? $locales[$lang] : $lang;
    }
}

if (! function_exists('sq')) {
    function sq(string $ip, int $port, int $timeout = 3, int $engine = 1)
    {
        $Query = new SourceQuery();

        $Query->Connect($ip, $port, $timeout, $engine);

        return $Query;
    }
}

if (! function_exists('markdown')) {
    function markdown()
    {
        return app(Parser::class);
    }
}

if (! function_exists('default_date_format')) {
    function default_date_format(bool $short = false)
    {
        switch (app()->getLang()) {
            case "ru":
                return $short ? "d.m.Y" : "d.m.Y H:i:s";
            case "en":
                return $short ? "m/d/Y" : "m/d/Y h:i:s A";
            case "es":
                return $short ? "d/m/Y" : "d/m/Y H:i:s";
            case "fr":
                return $short ? "d/m/Y" : "d/m/Y H:i:s";
            case "zh":
                return $short ? "Y-m-d" : "Y-m-d H:i:s";
            default:
                return $short ? "d.m.Y" : "d.m.Y H:i:s";
        }
    }
}

// V0.1o
if (! function_exists('validator')) {
    function validator()
    {
        return new FluteValidator;
    }
}

if (! function_exists('carbon')) {
    function carbon($time = null)
    {
        Carbon::setLocale(translation()->getLocale());
        CarbonInterval::setLocale(translation()->getLocale());

        return Carbon::parse($time);
    }
}

if (! function_exists('html_attributes')) {
    function html_attributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        return $html;
    }
}

if (! function_exists('is_cli')) {
    function is_cli(): bool
    {
        return php_sapi_name() === 'cli' || defined('STDIN');
    }
}

if (PHP_VERSION_ID < 80400 && ! function_exists('mb_ucfirst')) {
    function mb_ucfirst(string $str, ?string $encoding = null): string
    {
        if ($encoding === null) {
            $encoding = mb_internal_encoding();
        }
        return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, null, $encoding);
    }
}
