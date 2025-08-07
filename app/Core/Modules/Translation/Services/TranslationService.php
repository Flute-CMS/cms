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
     * Primary fallback locale used when key is missing in current locale.
     */
    protected ?string $primaryFallback = null;

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

        $cacheDir = config('lang.cache') ? path('storage/app/translations') : null;
        $debug = is_debug();

        $this->translator = new Translator($defaultLocale, null, $cacheDir, $debug);

        $this->performance = (bool) config('lang.cache') || is_performance();

        $this->translator->addLoader('file', new PhpFileLoader());
        $this->translator->setLocale($defaultLocale);
        $this->primaryFallback = $this->determinePrimaryFallback($availableLangs, $defaultLocale);
        $this->translator->setFallbackLocales($this->primaryFallback ? [$this->primaryFallback] : []);

        $this->listenEvents($eventDispatcher);

        $this->_importTranslationsForLocale($this->translator, $defaultLocale);
        if (config('lang.cache') && $this->primaryFallback && $this->primaryFallback !== $defaultLocale) {
            $this->_importTranslationsForLocale($this->translator, $this->primaryFallback);
        }

        Carbon::setLocale($this->translator->getLocale());
        CarbonInterval::setLocale($this->translator->getLocale());
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
                $result = $translator->trans($translationKey, $extendedReplacements, $domain, $locale);
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

        try {
            $loader = new PhpFileLoader();
            $cataloguePart = $loader->load($file, $locale, $domain);
            $this->translator->getCatalogue($locale)->add($cataloguePart->all($domain), $domain);
        } catch (\Throwable $e) {
            // ignore merge errors in runtime
        }

        $this->loadedDomains[$locale][$domain] = true;
        $this->domainFileIndex[$locale][$domain] = $file;
    }

    /**
     * Flush compiled catalogue cache for given locale (call explicitly when translations are modified).
     */
    public function flushLocaleCache(string $locale): void
    {
        if (!config('lang.cache')) {
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

        if (config('lang.cache')) {
            $cacheDir = path('storage/app/translations');
            foreach (glob($cacheDir . '/catalogue.' . $locale . '.*.php') as $cachedFile) {
                @unlink($cachedFile);
            }
            foreach (glob($cacheDir . '/catalogue.' . $locale . '.*.php.meta') as $cachedMeta) {
                @unlink($cachedMeta);
            }
        }

        $this->translator->addResource('file', $file, $locale, $domain);

        try {
            $loader = new \Symfony\Component\Translation\Loader\PhpFileLoader();
            $cataloguePart = $loader->load($file, $locale, $domain);
            $this->translator->getCatalogue($locale)->add($cataloguePart->all($domain), $domain);
        } catch (\Throwable $e) {
            // Silently ignore merge errors – translator will still pick up changes on next request
        }

        $this->loadedDomains[$locale][$domain] = true;
    }

    /**
     * Ensure domain is loaded for a specific locale.
     */
    protected function ensureDomainLoaded(string $domain, string $locale): void
    {
        if (isset($this->loadedDomains[$locale][$domain])) {
            return;
        }
        $this->loadDomain($domain, $locale);
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

        // Negative cache to avoid repeated filesystem checks
        $this->domainFileIndex[$locale][$domain] = '';

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
     * This centralizes module and package translation loading with proper caching and
     * catalogue compilation.
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
                ];
            }

            return $files;
        }, $cacheDuration);

        if (empty($translationFiles)) {
            $this->loadedDirectories[$directory] = true;

            return;
        }

        $currentLocale = app()->getLang();
        $fallbacks = $this->translator->getFallbackLocales();

        $filesByLocale = [];
        foreach ($translationFiles as $file) {
            $filesByLocale[$file['locale']][] = $file;
        }

        $localesToProcess = config('lang.cache') ? array_values(array_unique(array_filter([$currentLocale, $fallbacks[0] ?? null]))) : [$currentLocale];

        foreach ($localesToProcess as $locale) {
            $filesForLocale = $filesByLocale[$locale] ?? [];
            if (!$filesForLocale) {
                continue;
            }

            $needsRefresh = true;
            if (config('lang.cache')) {
                $cacheDir = path('storage/app/translations');
                $compiled = glob($cacheDir . '/catalogue.' . $locale . '.*.php');
                if ($compiled) {
                    $compiledMtime = max(array_map('filemtime', $compiled));
                    $latestSource = max(array_map(fn ($f) => filemtime($f['path']), $filesForLocale));
                    $needsRefresh = $latestSource > $compiledMtime;
                }
            }

            if ($needsRefresh && config('lang.cache')) {
                $this->flushLocaleCache($locale);
            }

            foreach ($filesForLocale as $file) {
                $this->registerResource($file['path'], $file['locale'], $file['domain']);
            }

            if ($needsRefresh && config('lang.cache')) {
                $this->translator->getCatalogue($locale);
            }
        }

        $this->loadedDirectories[$directory] = true;
        $this->translationDirectories[$directory] = true;
    }

}
