<?php

declare(strict_types = 1);

namespace Flute\Core\Services;

use Flute\Core\App;
use Flute\Core\Cache\AbstractCacheDriver;
use Flute\Core\Cache\CacheManager;
use Flute\Core\Exceptions\ForcedRedirectException;
use Flute\Core\Exceptions\RequestValidateException;
use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\ModulesManager\ModuleInformation;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\Theme\ThemeManager;
use RuntimeException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class CrashReportService
{
    private const MAX_TRACE_FRAMES = 15;
    private const RATE_LIMIT_FILE_TTL = 300;
    private const HOURLY_LIMIT = 10;

    private const SENSITIVE_KEYS = [
        'password',
        'secret',
        'token',
        'key',
        'auth',
        'cookie',
        'session',
        'credential',
        'dsn',
        'api_key',
        'authorization',
        'private',
        'salt',
        'hash',
    ];

    private const IGNORED_EXCEPTIONS = [
        ResourceNotFoundException::class,
        MethodNotAllowedException::class,
        ForcedRedirectException::class,
        TooManyRequestsException::class,
        RequestValidateException::class,
    ];

    private static array $buffer = [];
    private static bool $shutdownRegistered = false;
    private static ?array $configCache = null;

    public static function capture(\Throwable $e, array $context = []): void
    {
        try {
            $config = self::loadConfig();

            if (empty($config['share']) || !self::shouldReport($e)) {
                return;
            }

            $source = (string) ( $context['source'] ?? 'unknown' );
            $fingerprint = self::fingerprint($e, $source);

            if (!self::checkRateLimit($fingerprint)) {
                return;
            }

            self::$buffer[] = self::buildPayload($e, $context, $fingerprint);
            self::ensureShutdown();
        } catch (\Throwable) {
        }
    }

    public static function captureFatal(array $error): void
    {
        try {
            $config = self::loadConfig();

            if (empty($config['share'])) {
                return;
            }

            $fatalFile = (string) ( $error['file'] ?? '' );
            $fatalLine = (int) ( $error['line'] ?? 0 );
            $fingerprint = md5('FatalError:' . $fatalFile . ':' . $fatalLine);

            if (!self::checkRateLimit($fingerprint)) {
                return;
            }

            $payload = [
                'exception_class' => 'FatalError',
                'message' => self::scrubString((string) ( $error['message'] ?? 'Unknown fatal error' )),
                'code' => $error['type'] ?? 0,
                'file' => self::relativePath((string) ( $error['file'] ?? '' )),
                'line' => (int) ( $error['line'] ?? 0 ),
                'trace' => [],
                'php_version' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
                'cms_version' => self::getCmsVersion(),
                'modules' => self::getModulesList(),
                'themes' => self::getThemesList(),
                'url_path' => self::safeUrlPath(),
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'fingerprint' => $fingerprint,
                'timestamp' => date('c'),
                'source' => 'fatal',
            ];

            self::sendDirect([$payload], $config);
        } catch (\Throwable) {
        }
    }

    private static function shouldReport(\Throwable $e): bool
    {
        foreach (self::IGNORED_EXCEPTIONS as $class) {
            if ($e instanceof $class) {
                return false;
            }
        }

        if (method_exists($e, 'getStatusCode') && is_int($e->getStatusCode()) && $e->getStatusCode() < 500) {
            return false;
        }

        return true;
    }

    private static function fingerprint(\Throwable $e, string $source = ''): string
    {
        return md5(get_class($e) . ':' . $e->getFile() . ':' . $e->getLine() . ':' . $source);
    }

    private static function tryCacheAdapter(): ?AbstractCacheDriver
    {
        try {
            $app = App::getInstance();
            if (!$app->has(CacheManager::class)) {
                return null;
            }

            /** @var CacheManager $manager */
            $manager = $app->get(CacheManager::class);
            try {
                return $manager->getAdapter();
            } catch (RuntimeException) {
                if (!$app->has(ConfigurationService::class)) {
                    return null;
                }

                /** @var ConfigurationService $configuration */
                $configuration = $app->get(ConfigurationService::class);
                $rawCache = $configuration->get('cache');
                $cacheConfig = [];
                if (is_array($rawCache)) {
                    $cacheConfig = $rawCache;
                }

                return $manager->create($cacheConfig);
            }
        } catch (\Throwable) {
            return null;
        }
    }

    private static function checkRateLimit(string $fingerprint): bool
    {
        try {
            $cache = self::tryCacheAdapter();
            if ($cache instanceof AbstractCacheDriver) {
                $fpKey = 'crash_report:' . $fingerprint;
                if ($cache->has($fpKey)) {
                    return false;
                }
                $cache->set($fpKey, 1, self::RATE_LIMIT_FILE_TTL);

                $hourlyKey = 'crash_report_hourly_count';
                $count = (int) $cache->get($hourlyKey, 0);

                if ($count >= self::HOURLY_LIMIT) {
                    return false;
                }

                $cache->set($hourlyKey, $count + 1, 3600);

                return true;
            }
        } catch (\Throwable) {
        }

        $rateLimitDir = self::getRateLimitDir();

        if ($rateLimitDir === null) {
            return true;
        }

        $fpFile = $rateLimitDir . '/crash_' . $fingerprint;
        $mtime = is_file($fpFile) ? @filemtime($fpFile) : false;
        if ($mtime !== false && ( time() - $mtime ) < self::RATE_LIMIT_FILE_TTL) {
            return false;
        }

        $hourlyFile = $rateLimitDir . '/crash_hourly_count';
        $data = null;

        if (is_file($hourlyFile)) {
            $hourlyRaw = @file_get_contents($hourlyFile);
            $data = [];
            if (is_string($hourlyRaw) && $hourlyRaw !== '') {
                $parsed = json_decode($hourlyRaw, true);
                if (is_array($parsed)) {
                    $data = $parsed;
                }
            }
            $hour = (string) ( $data['hour'] ?? '' );
            $count = (int) ( $data['count'] ?? 0 );

            if ($hour === date('YmdH')) {
                if ($count >= self::HOURLY_LIMIT) {
                    return false;
                }
                $count++;
            } else {
                $hour = date('YmdH');
                $count = 1;
            }
            $data = ['hour' => $hour, 'count' => $count];
        } else {
            $data = ['hour' => date('YmdH'), 'count' => 1];
        }

        @file_put_contents($hourlyFile, json_encode($data));
        @touch($fpFile);

        return true;
    }

    private static function buildPayload(\Throwable $e, array $context, string $fingerprint): array
    {
        return [
            'exception_class' => get_class($e),
            'message' => self::scrubString($e->getMessage()),
            'code' => $e->getCode(),
            'file' => self::relativePath($e->getFile()),
            'line' => $e->getLine(),
            'trace' => self::buildTrace($e),
            'php_version' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            'cms_version' => self::getCmsVersion(),
            'modules' => self::getModulesList(),
            'themes' => self::getThemesList(),
            'url_path' => self::safeUrlPath(),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'fingerprint' => $fingerprint,
            'timestamp' => date('c'),
            'source' => (string) ( $context['source'] ?? 'unknown' ),
        ];
    }

    private static function buildTrace(\Throwable $e): array
    {
        $frames = [];
        $trace = $e->getTrace();
        $limit = min(count($trace), self::MAX_TRACE_FRAMES);

        for ($i = 0; $i < $limit; $i++) {
            if (!isset($trace[$i]) || !is_array($trace[$i])) {
                continue;
            }
            $frame = $trace[$i];
            $frames[] = [
                'file' => self::relativePath((string) ( $frame['file'] ?? '' )),
                'line' => (int) ( $frame['line'] ?? 0 ),
                'class' => isset($frame['class']) && is_string($frame['class']) ? $frame['class'] : null,
                'function' => isset($frame['function']) && is_string($frame['function']) ? $frame['function'] : null,
            ];
        }

        return $frames;
    }

    private static function getModulesList(): array
    {
        try {
            $app = App::getInstance();
            if (!$app->has(ModuleManager::class)) {
                return [];
            }

            /** @var ModuleManager $manager */
            $manager = $app->get(ModuleManager::class);
            $modules = [];

            $active = $manager->getActive();
            if (!is_iterable($active)) {
                return [];
            }

            foreach ($active as $module) {
                if (!$module instanceof ModuleInformation) {
                    continue;
                }
                $updater = new \Flute\Core\Update\Updaters\ModuleUpdater($module);
                $modules[$module->key] = $updater->getCurrentVersion();
            }

            return $modules;
        } catch (\Throwable) {
            return [];
        }
    }

    private static function getThemesList(): array
    {
        try {
            $app = App::getInstance();
            if (!$app->has(ThemeManager::class)) {
                return [];
            }

            /** @var ThemeManager $themeManager */
            $themeManager = $app->get(ThemeManager::class);
            $themes = [];

            $installed = $themeManager->getInstalledThemes();
            if (!is_iterable($installed)) {
                return [];
            }

            foreach ($installed as $theme) {
                if (!is_object($theme)) {
                    continue;
                }
                $key = property_exists($theme, 'key') ? $theme->key : null;
                if (!is_string($key) || $key === '') {
                    continue;
                }
                $version = isset($theme->version) && is_string($theme->version) ? $theme->version : '1.0.0';
                $themes[$key] = $version;
            }

            return $themes;
        } catch (\Throwable) {
            return [];
        }
    }

    private static function ensureShutdown(): void
    {
        if (self::$shutdownRegistered) {
            return;
        }

        self::$shutdownRegistered = true;

        register_shutdown_function(static function (): void {
            self::flush();
        });
    }

    private static function flush(): void
    {
        if (empty(self::$buffer)) {
            return;
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        $config = self::loadConfig();
        $reports = self::$buffer;
        self::$buffer = [];

        try {
            $app = App::getInstance();
            if ($app->has(FluteApiClient::class)) {
                $client = $app->get(FluteApiClient::class);
                if ($client instanceof FluteApiClient) {
                    $client->post('/api/crash-reports', [
                        'json' => ['reports' => $reports],
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'User-Agent' => 'Flute-CMS/' . self::getCmsVersion(),
                        ],
                    ]);

                    return;
                }
            }
        } catch (\Throwable) {
        }

        self::sendDirect($reports, $config);
    }

    private static function sendDirect(array $reports, array $config): void
    {
        if (!function_exists('curl_init')) {
            return;
        }

        $url = rtrim((string) ( $config['flute_market_url'] ?? 'https://flute-cms.com' ), '/') . '/api/crash-reports';
        $body = json_encode(['reports' => $reports]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: Flute-CMS/' . self::getCmsVersion(),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        @curl_exec($ch);
        @curl_close($ch);
    }

    private static function loadConfig(): array
    {
        if (self::$configCache !== null) {
            return self::$configCache;
        }

        try {
            $app = App::getInstance();
            if ($app->has(ConfigurationService::class)) {
                /** @var ConfigurationService $cfg */
                $cfg = $app->get(ConfigurationService::class);
                self::$configCache = [
                    'share' => (bool) $cfg->get('app.share', false),
                    'flute_market_url' => (string) $cfg->get('app.flute_market_url', 'https://flute-cms.com'),
                ];

                return self::$configCache;
            }
        } catch (\Throwable) {
        }

        $configFile = self::resolveBasePath() . '/config/app.php';

        if (!is_file($configFile)) {
            return self::$configCache = ['share' => false];
        }

        $config = @include $configFile;

        if (!is_array($config)) {
            return self::$configCache = ['share' => false];
        }

        return self::$configCache = [
            'share' => !empty($config['share']),
            'flute_market_url' => $config['flute_market_url'] ?? 'https://flute-cms.com',
        ];
    }

    private static function getCmsVersion(): string
    {
        try {
            return App::VERSION;
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    private static function scrubString(string $value): string
    {
        $patterns = [];

        foreach (self::SENSITIVE_KEYS as $key) {
            $patterns[] = '/(' . preg_quote($key, '/') . '[\s]*[=:]\s*)[^\s,;]+/i';
        }

        return (string) preg_replace($patterns, '$1[REDACTED]', $value);
    }

    private static function resolveBasePath(): string
    {
        if (defined('BASE_PATH')) {
            return (string) constant('BASE_PATH');
        }

        return dirname(__DIR__, 2);
    }

    private static function relativePath(string $path): string
    {
        if (!defined('BASE_PATH') || $path === '') {
            return $path;
        }

        $base = str_replace('\\', '/', rtrim((string) constant('BASE_PATH'), '\\/')) . '/';
        $path = str_replace('\\', '/', $path);

        if (str_starts_with($path, $base)) {
            return substr($path, strlen($base));
        }

        return $path;
    }

    private static function safeUrlPath(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    }

    private static function getRateLimitDir(): ?string
    {
        $dir = self::resolveBasePath() . '/storage/crash-reports';

        if (!is_dir($dir) && !@mkdir($dir, 0o755, true)) {
            return null;
        }

        return $dir;
    }
}
