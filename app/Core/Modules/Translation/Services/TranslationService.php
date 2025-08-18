<?php

namespace Flute\Core\Modules\Translation\Services;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Flute\Core\Events\RoutingStartedEvent;
use Flute\Core\Modules\Translation\Events\LangChangedEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;

class TranslationService
{
    protected Translator $translator;
    protected bool $performance;
    protected const CACHE_TIME = 24 * 60 * 60; // 24 hours


    /**
     * Track loaded domains per locale to avoid duplicate merges.
     * [locale => [domain => true]]
     */
    protected array $loadedDomains = [];

    /**
     * Track absolute file paths already registered this request to skip duplicates.
     * @var array<string,bool>
     */
    protected array $loadedFiles = [];

    /**
     * Track directories whose translations have been loaded to avoid duplicate processing.
     * @var array<string,bool>
     */
    protected array $loadedDirectories = [];

    /**
     * Directories registered via loadTranslationsFromDirectory for module/package translations.
     * Used for lazy domain resolution across modules.
     * @var array<string,bool>
     */
    protected array $translationDirectories = [];

    /**
     * Cached mapping of [locale][domain] => file path for quick lazy loads.
     * @var array<string,array<string,string>>
     */
    protected array $domainFileIndex = [];

    /**
     * Track locales that have been explicitly recompiled during this request
     * after dynamic translation directories were registered.
     * @var array<string,bool>
     */
    protected array $recompiledLocales = [];

    /**
     * Latest discovered source files per locale used to update cache after compilation.
     * @var array<string,array<string,int>> path => mtime
     */
    protected array $latestDiscoveredFilesByLocale = [];

    /**
     * Primary fallback locale used when key is missing in current locale.
     */
    protected ?string $primaryFallback = null;

    /**
     * When true, multiple directories are being loaded in bulk and cache invalidation
     * should be deferred and performed once per locale at the end of bulk load.
     */
    protected bool $bulkLoad = false;

    public function __construct(EventDispatcher $eventDispatcher)
    {
        $availableLangs = (array) config('lang.available');

        $requestedLang = request()->input('lang');
        $cookieLang = cookie()->get('current_lang');

        $defaultLocale = null;

        foreach ([$requestedLang, $cookieLang, app()->getLang(), config('lang.locale')] as $candidate) {
            if ($candidate && in_array($candidate, $availableLangs, true)) {
                $defaultLocale = $candidate;

                break;
            }
        }

        $defaultLocale = $defaultLocale ?: (string) (config('lang.locale') ?? 'en');

        if (app()->getLang() !== $defaultLocale) {
            app()->setLang($defaultLocale);
        }

        // Enable on-disk translation caching only when performance mode is requested
        // and the required Symfony Config classes are available. Missing the
        // symfony/config package causes Translator to throw a fatal error when
        // attempting to create the config cache factory, so detect that and
        // silently fall back to non-cached mode.
        $requestedCacheDir = is_performance() ? path('storage/app/translations') : null;
        $debug = is_debug();

        $cacheDir = $requestedCacheDir;
        if ($requestedCacheDir) {
            if (!class_exists('Symfony\\Component\\Config\\ConfigCacheFactory') && !class_exists('Symfony\\Component\\Config\\ConfigCacheFactoryInterface')) {
                logs()->warning('Symfony Config component is missing; disabling translation catalogue cache.');
                $cacheDir = null;
            }
        }

        $this->translator = new Translator($defaultLocale, null, $cacheDir, $debug);

        $this->performance = ($cacheDir !== null);

        $this->translator->addLoader('file', new PhpFileLoader());
        $this->translator->setLocale($defaultLocale);
        $this->primaryFallback = $this->determinePrimaryFallback($availableLangs, $defaultLocale);
        $this->translator->setFallbackLocales($this->primaryFallback ? [$this->primaryFallback] : []);

        $this->listenEvents($eventDispatcher);

        register_shutdown_function(function () {
            if (!is_performance()) {
                return;
            }
            foreach ($this->latestDiscoveredFilesByLocale as $locale => $paths) {
                $cacheKey = 'translation.compiled.sources.' . $locale;
                $existing = (array) cache()->get($cacheKey, []);
                $updated = array_replace($existing, $paths);
                cache()->set($cacheKey, $updated, self::CACHE_TIME);
            }
        });

        $this->_importTranslationsForLocale($this->translator, $defaultLocale);
        if (is_performance() && $this->primaryFallback && $this->primaryFallback !== $defaultLocale) {
            $this->_importTranslationsForLocale($this->translator, $this->primaryFallback);
        }

        $this->registerKnownTranslationDirectories();

        Carbon::setLocale($this->translator->getLocale());
        CarbonInterval::setLocale($this->translator->getLocale());
    }

    /**
     * Discover and register translation directories from modules and admin packages early.
     */
    protected function registerKnownTranslationDirectories(): void
    {
        $dirs = cache()->callback('translation.known_dirs', function () {
            $result = [];
            // Modules: app/Modules/*/Resources/lang
            $modulesRoot = path('app/Modules');
            if (is_dir($modulesRoot)) {
                foreach (glob($modulesRoot . '/*/Resources/lang', GLOB_NOSORT) as $dir) {
                    if (is_dir($dir)) {
                        $result[] = $dir;
                    }
                }
            }
            // Admin packages: app/Core/Modules/Admin/Packages/*/Resources/lang
            $adminPkgsRoot = path('app/Core/Modules/Admin/Packages');
            if (is_dir($adminPkgsRoot)) {
                foreach (glob($adminPkgsRoot . '/*/Resources/lang', GLOB_NOSORT) as $dir) {
                    if (is_dir($dir)) {
                        $result[] = $dir;
                    }
                }
            }
            // Ensure deterministic order so lazy resolution is stable
            sort($result);

            return $result;
        }, self::CACHE_TIME);

        $this->bulkLoad = true;
        foreach ($dirs as $dir) {
            $this->translationDirectories[$dir] = true;
            $this->loadTranslationsFromDirectory($dir, self::CACHE_TIME);
        }
        $this->bulkLoad = false;

        if (is_performance()) {
            $localesNeedingFlush = [];
            foreach ($this->latestDiscoveredFilesByLocale as $locale => $pathsWithMtime) {
                $cacheKeyCompiled = 'translation.compiled.sources.' . $locale;
                $previous = (array) cache()->get($cacheKeyCompiled, []);
                foreach ($pathsWithMtime as $path => $mtime) {
                    $prevMtime = $previous[$path] ?? 0;
                    if ($prevMtime < $mtime) {
                        $localesNeedingFlush[$locale] = true;

                        break;
                    }
                }
                if (!isset($localesNeedingFlush[$locale])) {
                    foreach (array_keys($pathsWithMtime) as $path) {
                        if (!array_key_exists($path, $previous)) {
                            $localesNeedingFlush[$locale] = true;

                            break;
                        }
                    }
                }
            }
            foreach (array_keys($localesNeedingFlush) as $locale) {
                if (!isset($this->recompiledLocales[$locale])) {
                    $this->flushLocaleCache($locale);
                    $this->recompiledLocales[$locale] = true;
                }
            }
        }
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    protected function _importTranslationsForLocale(Translator $translator, string $locale)
    {
        $langDir = path('i18n/' . $locale);
        if (!is_dir($langDir)) {
            return;
        }

        $cacheKey = 'translation.core.files.' . $locale;

        $domains = cache()->callback($cacheKey, function () use ($langDir) {
            $finder = finder();
            $finder->files()->in($langDir)->name('*.php');

            $result = [];
            foreach ($finder as $file) {
                $result[] = [
                    'domain' => basename($file->getFilename(), '.php'),
                    'path' => $file->getPathname(),
                ];
            }

            return $result;
        }, self::CACHE_TIME);

        foreach ($domains as $domainInfo) {
            $translator->addResource('file', $domainInfo['path'], $locale, $domainInfo['domain']);
            $this->loadedDomains[$locale][$domainInfo['domain']] = true;
            $this->domainFileIndex[$locale][$domainInfo['domain']] = $domainInfo['path'];
        }
    }

    protected function _importTranslations(Translator $translator)
    {
        $langDir = path('i18n');

        $cacheKey = 'translation.core.files';

        $files = cache()->callback($cacheKey, function () use ($langDir) {
            $finder = finder();
            $finder->files()->in($langDir)->name('*.php');

            $result = [];
            foreach ($finder as $file) {
                $result[] = [
                    'locale' => $file->getRelativePath(),
                    'domain' => basename($file->getFilename(), '.php'),
                    'path' => $file->getPathname(),
                ];
            }

            return $result;
        }, self::CACHE_TIME);

        foreach ($files as $file) {
            $translator->addResource('file', $file['path'], $file['locale'], $file['domain']);
            $this->loadedDomains[$file['locale']][$file['domain']] = true;
        }
    }

    /**
     * Listen to the lang changed event.
     *
     * @return void
     */
    protected function listenEvents(EventDispatcher $eventDispatcher)
    {
        $eventDispatcher->addListener(RoutingStartedEvent::NAME, [$this, 'onRoutingStarted']);
        $eventDispatcher->addListener(LangChangedEvent::NAME, [$this, 'onLangChanged']);
    }

    /**
     * Get the translator instance.
     *
     * @return Translator
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Get the user's preferred language.
     *
     * @return string
     */
    public function getPreferredLanguage(): string
    {
        return substr(request()->getPreferredLanguage((array) app('lang.available')), 0, 2);
    }

    /**
     * Handle the LangChangedEvent.
     *
     * @param LangChangedEvent $event
     */
    public function onLangChanged(LangChangedEvent $event): void
    {
        $newLang = $event->getNewLang();
        $this->translator->setLocale($newLang);

        if (!isset($this->loadedDomains[$newLang])) {
            $this->_importTranslationsForLocale($this->translator, $newLang);
        }

        $this->primaryFallback = $this->determinePrimaryFallback((array) config('lang.available'), $newLang);
        $this->translator->setFallbackLocales($this->primaryFallback ? [$this->primaryFallback] : []);

        Carbon::setLocale($newLang);
        CarbonInterval::setLocale($newLang);
    }

    /**
     * Register the lang parameter and change language.
     *
     * @return void
     */
    protected function registerLangGet()
    {
        if ($lang = request()->input('lang')) {
            if (in_array($lang, (array) app('lang.available'))) {
                app()->setLang($lang);
                cookie()->set('current_lang', $lang);
            }
        }
    }

    /**
     * Set the translation route if the application is installed.
     */
    public function onRoutingStarted(RoutingStartedEvent $routingStartedEvent): void
    {
        if (!app()->getLang()) {
            app()->setLang(config('lang.locale'));
        }

        $this->registerLangGet();
    }

    /**
     * Translate keys using Symfony's translation system.
     *
     * Supports arguments in Laravel format (e.g., :name) and Symfony format (e.g., %name%).
     *
     * @param string $key
     * @param array $replacements
     * @param string|null $locale
     * @return string
     */
    public function trans(string $key, array $replacements = [], ?string $locale = null): string
    {
        $translator = $this->getTranslator();
        $locale ??= $translator->getLocale();

        if (strpos($key, '.') !== false) {
            [$domain, $translationKey] = explode('.', $key, 2);
            $this->ensureDomainLoaded($domain, $locale);
        }

        $extendedReplacements = $replacements;
        foreach ($replacements as $rKey => $rValue) {
            if (!is_string($rKey)) {
                continue;
            }
            if (strpos($rKey, ':') !== 0 && !(substr($rKey, 0, 1) === '%' && substr($rKey, -1) === '%')) {
                $extendedReplacements[':' . $rKey] = $rValue;
                $extendedReplacements['%' . $rKey . '%'] = $rValue;
            }
        }

        if (strpos($key, '.') !== false) {
            [$domain, $translationKey] = explode('.', $key, 2);

            $result = $translator->trans($translationKey, $extendedReplacements, $domain, $locale);

            if ($result !== $translationKey) {
                return $result;
            }

            if ($this->primaryFallback && $this->primaryFallback !== $locale) {
                $this->ensureDomainLoaded($domain, $this->primaryFallback);
                $result = $translator->trans($translationKey, $extendedReplacements, $domain, $this->primaryFallback);
                if ($result !== $translationKey) {
                    return $result;
                }
            }

            return $key;
        }

        return $translator->trans($key, $extendedReplacements, null, $locale);
    }

    /**
     * Load a specific domain for a locale.
     *
     * @param string $domain
     * @param string $locale
     * @return void
     */
    public function registerResource(string $file, string $locale, string $domain): void
    {
        if (!file_exists($file)) {
            return;
        }

        if (isset($this->loadedFiles[$file])) {
            return;
        }
        $this->loadedFiles[$file] = true;

        $this->translator->addResource('file', $file, $locale, $domain);

        $this->loadedDomains[$locale][$domain] = true;
        $this->domainFileIndex[$locale][$domain] = $file;
    }

    /**
     * Flush compiled catalogue cache for given locale (call explicitly when translations are modified).
     */
    public function flushLocaleCache(string $locale): void
    {
        if (!is_performance()) {
            return;
        }
        $cacheDir = path('storage/app/translations');
        foreach (glob($cacheDir . '/catalogue.' . $locale . '.*.php') as $cachedFile) {
            @unlink($cachedFile);
        }
        foreach (glob($cacheDir . '/catalogue.' . $locale . '.*.php.meta') as $cachedMeta) {
            @unlink($cachedMeta);
        }
        unset($this->loadedDomains[$locale]);
    }

    protected function loadDomain(string $domain, string $locale): void
    {
        $file = $this->resolveDomainFile($locale, $domain);
        if ($file === null) {
            return;
        }

        $this->translator->addResource('file', $file, $locale, $domain);

        $this->loadedDomains[$locale][$domain] = true;
    }

    /**
     * Ensure domain is loaded for a specific locale.
     */
    protected function ensureDomainLoaded(string $domain, string $locale): void
    {
        $file = $this->resolveDomainFile($locale, $domain);
        if ($file === null) {
            return;
        }
        if (!isset($this->loadedFiles[$file])) {
            $this->registerResource($file, $locale, $domain);
        }
    }

    /**
     * Find a domain file for a given locale from core i18n or registered directories.
     */
    protected function resolveDomainFile(string $locale, string $domain): ?string
    {
        if (isset($this->domainFileIndex[$locale][$domain])) {
            $cached = $this->domainFileIndex[$locale][$domain];

            return $cached !== '' ? $cached : null;
        }

        $corePath = path('i18n/' . $locale . '/' . $domain . '.php');
        if (file_exists($corePath)) {
            return $this->domainFileIndex[$locale][$domain] = $corePath;
        }

        foreach (array_keys($this->translationDirectories) as $dir) {
            $candidate = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $domain . '.php';
            if (file_exists($candidate)) {
                return $this->domainFileIndex[$locale][$domain] = $candidate;
            }
        }

        return null;
    }

    /**
     * Determine a reasonable single fallback locale.
     */
    protected function determinePrimaryFallback(array $availableLangs, string $current): ?string
    {
        if (in_array('en', $availableLangs, true) && $current !== 'en') {
            return 'en';
        }
        foreach ($availableLangs as $lang) {
            if ($lang !== $current) {
                return $lang;
            }
        }

        return null;
    }

    /**
     * Load translation resources from a directory structured as <dir>/<locale>/<domain>.php.
     */
    public function loadTranslationsFromDirectory(string $directory, int $cacheDuration = self::CACHE_TIME): void
    {
        if (isset($this->loadedDirectories[$directory]) || !is_dir($directory)) {
            return;
        }

        $cacheKey = 'translation.dir.' . md5($directory);
        $translationFiles = cache()->callback($cacheKey, function () use ($directory) {
            $finder = finder();
            $finder->files()->in($directory)->name('*.php');

            $files = [];
            foreach ($finder as $file) {
                $files[] = [
                    'path' => $file->getPathname(),
                    'locale' => $file->getRelativePath(),
                    'domain' => basename($file->getFilename(), '.php'),
                    'mtime' => filemtime($file->getPathname()),
                ];
            }

            return $files;
        }, $cacheDuration);

        if (empty($translationFiles)) {
            $this->loadedDirectories[$directory] = true;

            return;
        }

        foreach ($translationFiles as $file) {
            $this->domainFileIndex[$file['locale']][$file['domain']] = $file['path'];
            $this->latestDiscoveredFilesByLocale[$file['locale']][$file['path']] = $file['mtime'];
        }

        $filesByLocale = [];
        foreach ($translationFiles as $file) {
            $filesByLocale[$file['locale']][] = $file;
        }

        $localesToProcess = array_keys($filesByLocale);

        if (!$this->bulkLoad && is_performance()) {
            $localesNeedingFlush = [];
            foreach ($localesToProcess as $locale) {
                $filesForLocale = $filesByLocale[$locale] ?? [];
                if (!$filesForLocale) {
                    continue;
                }
                $cacheKeyCompiled = 'translation.compiled.sources.' . $locale;
                $previous = (array) cache()->get($cacheKeyCompiled, []);
                foreach ($filesForLocale as $f) {
                    $mtime = $f['mtime'] ?? @filemtime($f['path']) ?: 0;
                    $this->latestDiscoveredFilesByLocale[$locale][$f['path']] = $mtime;
                    $prevMtime = $previous[$f['path']] ?? 0;
                    if ($prevMtime < $mtime) {
                        $localesNeedingFlush[$locale] = true;
                    }
                }
                foreach ($filesForLocale as $f) {
                    if (!array_key_exists($f['path'], $previous)) {
                        $localesNeedingFlush[$locale] = true;

                        break;
                    }
                }
            }
            foreach (array_keys($localesNeedingFlush) as $locale) {
                if (!isset($this->recompiledLocales[$locale])) {
                    $this->flushLocaleCache($locale);
                    $this->recompiledLocales[$locale] = true;
                }
            }
        }

        $currentLocale = app()->getLang();
        $fallbacks = $this->translator->getFallbackLocales();
        $eagerLocales = array_unique(array_filter(array_merge([$currentLocale], $fallbacks)));

        foreach ($localesToProcess as $locale) {
            if (!in_array($locale, $eagerLocales, true)) {
                continue;
            }
            $filesForLocale = $filesByLocale[$locale] ?? [];
            if (!$filesForLocale) {
                continue;
            }

            foreach ($filesForLocale as $file) {
                $this->registerResource($file['path'], $file['locale'], $file['domain']);
            }
        }

        $this->loadedDirectories[$directory] = true;
        $this->translationDirectories[$directory] = true;
    }
}
