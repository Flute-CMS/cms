<?php

namespace Flute\Core\Services;

use Flute\Core\Events\RoutingStartedEvent;
use Flute\Core\Http\Controllers\TranslationController;
use Nette\Forms\Validator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;
use Flute\Core\Events\LangChangedEvent;

class LanguageService
{
    protected Translator $translator;
    protected bool $performance;
    protected const CACHE_TIME = 24 * 60 * 60;

    /**
     * LanguageService constructor.
     * Create a new translator and import translations.
     */
    public function __construct(EventDispatcher $eventDispatcher)
    {
        $defaultLocale = app()->getLang() ?? config('lang.locale');
        $cacheDir = (bool) config('lang.cache') === true ? path('storage/app/translations') : null;
        $this->translator = new Translator($defaultLocale, null, $cacheDir);

        $this->performance = is_performance();

        $this->translator->addLoader('file', new PhpFileLoader());
        $this->translator->setLocale($defaultLocale);
        $this->translator->setFallbackLocales(config('lang.available'));

        $this->listenEvents($eventDispatcher);
        $this->_importTranslations($this->translator);
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
        return substr(request()->server->get('HTTP_ACCEPT_LANGUAGE', ''), 0, 2);
    }

    /**
     * Handle the LangChangedEvent.
     *
     * @param LangChangedEvent $event
     */
    public function onLangChanged(LangChangedEvent $event): void
    {
        $this->translator->setLocale($event->getNewLang());
    }

    /**
     * Register the lang parameter and change language.
     * 
     * @return void
     */
    protected function registerLangGet()
    {
        // Я в курсе что сюда пихать это такое себе, но я не придумал куда еще это запихнуть
        if ($lang = request()->input('lang')) {
            if (in_array($lang, (array) app('lang.available'))) {
                app()->setLang($lang);
                cookie()->set('current_lang', $lang);
            }
        }
    }

    /**
     * Import translations from the i18n directory.
     *
     * @param Translator $translator
     */
    protected function _importTranslations(Translator $translator)
    {
        // Если придерживаться стратегии подгрузки только тех папок, которые нужны, это +0 к производительности.
        // да и кеш будет только выбранного перевода. Говно в общем подход
        // $langDir = path('i18n/'.$translator->getLocale());

        $langDir = path('i18n');
        $finder = finder();
        $finder->files()->in($langDir)->name('*.php');

        foreach ($finder as $file) {
            $locale = $file->getRelativePath();
            $domain = basename($file->getFilename(), '.php');
            $translator->addResource('file', $file->getPathname(), $locale, $domain);
        }
    }

    /**
     * Set the translation route if the application is installed.
     */
    public function onRoutingStarted(RoutingStartedEvent $routingStartedEvent): void
    {
        $routingStartedEvent->getRouteDispatcher()
            ->post('api/translate', [TranslationController::class, 'translate']);

        $this->registerLangGet();
        $this->setValidatorTranslations();
    }

    public function setValidatorTranslations(): void
    {
        Validator::$messages = $this->performance ? cache()->callback('validator.messages', function () {
            return $this->getValidatorTranslations();
        }, self::CACHE_TIME) : $this->getValidatorTranslations();
    }

    protected function getValidatorTranslations(): array
    {
        return [
            'Nette\\Forms\\Controls\\CsrfProtection::validateCsrf' => $this->trans('validator.session_expired'),
            ':equal' => $this->trans('validator.equal', ['%s']),
            ':notEqual' => $this->trans('validator.not_equal', ['%s']),
            ':filled' => $this->trans('validator.filled'),
            ':blank' => $this->trans('validator.blank'),
            ':minLength' => $this->trans('validator.min_length', ['%d']),
            ':maxLength' => $this->trans('validator.max_length', ['%d']),
            ':length' => $this->trans('validator.length', ['%d', '%d']),
            ':email' => $this->trans('validator.email'),
            ':url' => $this->trans('validator.url'),
            ':integer' => $this->trans('validator.integer'),
            ':float' => $this->trans('validator.float'),
            ':min' => $this->trans('validator.min', ['%d']),
            ':max' => $this->trans('validator.max', ['%d']),
            ':range' => $this->trans('validator.range', ['%d', '%d']),
            ':maxFileSize' => $this->trans('validator.max_file_size', ['%d']),
            ':maxPostSize' => $this->trans('validator.max_post_size', ['%d']),
            ':mimeType' => $this->trans('validator.mime_type'),
            ':image' => $this->trans('validator.image'),
            ':selectBoxValid' => $this->trans('validator.select_box_valid'),
            ':uploadControlValid' => $this->trans('validator.upload_control_valid'),
        ];
    }

    public function trans(string $key, array $replacements = [], string $locale = null): string
    {
        $translator = $this->getTranslator();
        $locale = $locale ?? $translator->getLocale();

        if (strpos($key, '.') !== false) {
            [$domain, $translationKey] = explode('.', $key, 2);
            return $translator->trans($translationKey, $replacements, $domain, $locale);
        }

        return $translator->trans($key, $replacements, null, $locale);
    }
}
