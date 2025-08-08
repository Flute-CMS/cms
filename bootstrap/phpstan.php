<?php

declare(strict_types=1);

// Lightweight bootstrap for PHPStan.
// Goal: provide constants and minimal helpers that static analysis expects
// without booting the full application or performing side-effects like redirects.

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

// Provide a minimal autoloader, but DO NOT require bootstrap/app.php
// to avoid constructing the application and running service providers.
$autoload = BASE_PATH . 'vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Define no-op stubs for global helper functions that may be referenced
// by analysed code paths during static analysis. These stubs must match
// signatures but avoid any runtime logic/side-effects.

if (!function_exists('is_cli')) {
    function is_cli(): bool { return true; }
}

if (!function_exists('is_debug')) {
    function is_debug(): bool { return true; }
}

if (!function_exists('path')) {
    function path(string $path = ''): string {
        return rtrim(BASE_PATH, '/').'/'.ltrim($path, '/');
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null) { return $default; }
}

if (!function_exists('app')) {
    function app($name = null) { return new class { public function __call($n,$a){ return null; } public function getLang(){ return 'en'; } public function setLang($l){} public function getTheme(){ return 'app/Themes/standard'; } }; }
}

if (!function_exists('finder')) {
    function finder() { return new Symfony\Component\Finder\Finder(); }
}

if (!function_exists('cache')) {
    function cache() { return new class { public function callback($k,$fn,$ttl){ return $fn(); } }; }
}

if (!function_exists('request')) {
    function request() { return new class { public function input($k){ return null; } public function getPreferredLanguage($a){ return 'en'; } public function ip(){ return '127.0.0.1'; } public function getPathInfo(){ return '/'; } }; }
}

if (!function_exists('cookie')) {
    function cookie() { return new class { public function get($k){ return null; } public function set($k,$v){} }; }
}

if (!function_exists('logs')) {
    function logs(){ return new class { public function emergency($m){} public function error($e){} }; }
}

if (!function_exists('template')) {
    function template(){ return new class { public function getAsset($p){ return $p; } public function getBlade(){ return new class { public function compiler(){ return new class { public function component($v,$a){} }; } }; } public function addNamespace($a,$b){} public function getTemplateAssets(){ return new class { public function getCompiler(){ return new class { public function setImportPaths($p){} }; } }; } }; }
}

if (!function_exists('router')) {
    function router(){ return new class { public function registerAttributeRoutes($a,$b){} }; }
}

if (!function_exists('url')) {
    function url(?string $path = null, array $params = []) { return new class($path){ private $p; public function __construct($p){ $this->p=$p; } public function current(){ return $this->p ?? '/'; } }; }
}

if (!function_exists('response')) {
    function response(){ return new class { public function redirect($to){ return ''; } }; }
}

if (!function_exists('translation')) {
    function translation(){ return new class { public function getLocale(){ return 'en'; } }; }
}


