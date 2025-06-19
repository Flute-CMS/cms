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
    protected $cache;
    protected array $loadedDomains = [];

    public function __construct(EventDispatcher $eventDispatcher)
    {
        $availableLangs = (array) config('lang.available');

        $requestedLang = request()->input('lang');
        $cookieLang    = cookie()->get('current_lang');

        $defaultLocale = null;

        foreach ([$requestedLang, $cookieLang, app()->getLang(), config('lang.locale')] as $candidate) {
            if ($candidate && in_array($candidate, $availableLangs, true)) {
                $defaultLocale = $candidate;
                break;
            }
        }

        if ($defaultLocale && app()->getLang() !== $defaultLocale) {
            app()->setLang($defaultLocale);
        }

        $cacheDir = config('lang.cache') ? path('storage/app/translations') : null;
        $debug = is_debug();

        $this->translator = new Translator($defaultLocale, null, $cacheDir, $debug);

        $this->performance = is_performance();

        $this->translator->addLoader('file', new PhpFileLoader());
        $this->translator->setLocale($defaultLocale);
        $this->translator->setFallbackLocales(config('lang.available'));

        $this->listenEvents($eventDispatcher);
        
        if ($this->performance) {
            $this->_importTranslationsForLocale($this->translator, $defaultLocale);
        } else {
            $this->_importTranslations($this->translator);
        }

        Carbon::setLocale($this->translator->getLocale());
        CarbonInterval::setLocale($this->translator->getLocale());
    }

    public function getLocale() : string
    {
        return $this->translator->getLocale();
    }

    protected function _importTranslationsForLocale(Translator $translator, string $locale)
    {
        $langDir = path('i18n/' . $locale);
        if (!is_dir($langDir)) {
            return;
        }
        
        $finder = finder();
        $finder->files()->in($langDir)->name('*.php');

        foreach ($finder as $file) {
            $domain = basename($file->getFilename(), '.php');
            $translator->addResource('file', $file->getPathname(), $locale, $domain);
            $this->loadedDomains[$locale][$domain] = true;
        }
    }

    protected function _importTranslations(Translator $translator)
    {
        $langDir = path('i18n');
        $finder = finder();
        $finder->files()->in($langDir)->name('*.php');

        $files = [];
        foreach ($finder as $key => $file) {
            $locale = $file->getRelativePath();
            $domain = basename($file->getFilename(), '.php');
            
            $files[] = [
                'locale' => $locale,
                'domain' => $domain,
                'path' => $file->getPathname(),
            ];
            
            $this->loadedDomains[$locale][$domain] = true;
        }

        foreach ($files as $file) {
            $translator->addResource('file', $file['path'], $file['locale'], $file['domain']);
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
    public function getTranslator() : Translator
    {
        return $this->translator;
    }

    /**
     * Get the user's preferred language.
     *
     * @return string
     */
    public function getPreferredLanguage() : string
    {
        return substr(request()->getPreferredLanguage((array) app('lang.available')), 0, 2);
    }

    /**
     * Handle the LangChangedEvent.
     *
     * @param LangChangedEvent $event
     */
    public function onLangChanged(LangChangedEvent $event) : void
    {
        $newLang = $event->getNewLang();
        $this->translator->setLocale($newLang);
                
        if (!isset($this->loadedDomains[$newLang]) && $this->performance) {
            $this->_importTranslationsForLocale($this->translator, $newLang);
        }

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
    public function onRoutingStarted(RoutingStartedEvent $routingStartedEvent) : void
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
    public function trans(string $key, array $replacements = [], string $locale = null) : string
    {
        $translator = $this->getTranslator();
        $locale = $locale ?? $translator->getLocale();

        if (strpos($key, '.') !== false) {
            [$domain, $translationKey] = explode('.', $key, 2);
            
            if ($this->performance && (!isset($this->loadedDomains[$locale]) || !isset($this->loadedDomains[$locale][$domain]))) {
                $this->loadDomain($domain, $locale);
            }
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

            if ($result === $translationKey) {
                return $key;
            }

            return $result;
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
    protected function loadDomain(string $domain, string $locale): void
    {
        $file = path('i18n/' . $locale . '/' . $domain . '.php');
        
        if (file_exists($file)) {
            $this->translator->addResource('file', $file, $locale, $domain);
            $this->loadedDomains[$locale][$domain] = true;
        }
    }
}
