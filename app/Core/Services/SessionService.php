<?php

namespace Flute\Core\Services;

use Flute\Core\Modules\Translation\Events\LangChangedEvent;
use Flute\Core\Modules\Translation\Services\TranslationService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

class SessionService implements SessionInterface
{
    private Session $session;

    /**
     * SessionService constructor.
     */
    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->session = new Session();
        $this->setSessionLanguage();

        $this->listen($eventDispatcher);
    }

    /**
     * Listen to change lang event.
     * 
     * @param EventDispatcher $eventDispatcher
     * 
     * @return void
     */
    public function listen(EventDispatcher $eventDispatcher): void
    {
        $eventDispatcher->addListener(LangChangedEvent::class, [$this, 'onLangChanged']);
    }

    /**
     * Handle the LangChangedEvent.
     *
     * This method is used as event listener to update the session language when the application language is changed.
     *
     * @param LangChangedEvent $event The LangChangedEvent instance.
     */
    public function onLangChanged(LangChangedEvent $event): void
    {
        $this->set('lang', $event->getNewLang());
    }

    /**
     * Sets the session language
     * 
     * @return void
     */
    protected function setSessionLanguage(): void
    {
        $availableLanguages = app('lang.available');
        $defaultLanguage = app('lang.locale');
        $currentCookieLang = cookie()->get('current_lang');
        $currentSessionLang = $this->session->get('lang');

        if (cookie()->has('current_lang') && in_array($currentCookieLang, (array) $availableLanguages)) {
            $lang = $currentCookieLang;
        } elseif (!$currentSessionLang) {
            $lang = app(TranslationService::class)->getPreferredLanguage();
        } else {
            $lang = in_array($currentSessionLang, (array) $availableLanguages) ? $currentSessionLang : $defaultLanguage;
        }

        app()->setLang($lang);
    }

    /**
     * Return flash bang
     * 
     * @return FlashBagInterface
     */
    public function getFlashBag(): FlashBagInterface
    {
        return $this->session->getFlashBag();
    }

    /**
     * Start the session.
     * 
     * @return bool
     */
    public function start(): bool
    {
        return $this->session->start();
    }

    /**
     * Set a session value.
     *
     * @param string $name The name of the session variable.
     * @param mixed $value The value to store in the session.
     */
    public function set(string $name, $value): void
    {
        $this->session->set($name, $value);
    }

    /**
     * Get a session value.
     *
     * @param string $name The name of the session variable.
     * @param mixed|null $default The default value to return if the session variable is not set.
     * @return mixed The value of the session variable or the default value.
     */
    public function get(string $name, $default = null): mixed
    {
        return $this->session->get($name, $default);
    }

    /**
     * Gets all session values.
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->session->all();
    }

    /**
     * Check if a session variable exists.
     *
     * @param string $name The name of the session variable.
     * @return bool True if the session variable exists, false otherwise.
     */
    public function has(string $name): bool
    {
        return $this->session->has($name);
    }

    /**
     * Remove a session variable.
     *
     * @param string $name The name of the session variable.
     */
    public function remove(string $name): mixed
    {
        return $this->session->remove($name);
    }

    /**
     * Clear all session variables.
     */
    public function clear(): void
    {
        $this->session->clear();
    }

    /**
     * Get the session ID.
     */
    public function getId(): string
    {
        return $this->session->getId();
    }

    /**
     * Set the session ID.
     */
    public function setId(string $id): void
    {
        $this->session->setId($id);
    }

    /**
     * Get the session name.
     */
    public function getName(): string
    {
        return $this->session->getName();
    }

    /**
     * Set the session name.
     */
    public function setName(string $name): void
    {
        $this->session->setName($name);
    }

    /**
     * Invalidate the session.
     */
    public function invalidate(?int $lifetime = null): bool
    {
        return $this->session->invalidate($lifetime);
    }

    /**
     * Migrate the session to a new session ID.
     */
    public function migrate(bool $destroy = false, ?int $lifetime = null): bool
    {
        return $this->session->migrate($destroy, $lifetime);
    }

    /**
     * Save and close the session.
     */
    public function save(): void
    {
        $this->session->save();
    }

    /**
     * Replace session attributes.
     */
    public function replace(array $attributes): void
    {
        $this->session->replace($attributes);
    }

    /**
     * Check if the session has started.
     */
    public function isStarted(): bool
    {
        return $this->session->isStarted();
    }

    /**
     * Register a session bag.
     */
    public function registerBag(SessionBagInterface $bag): void
    {
        $this->session->registerBag($bag);
    }

    /**
     * Get a session bag.
     */
    public function getBag(string $name): SessionBagInterface
    {
        return $this->session->getBag($name);
    }

    /**
     * Get the session metadata bag.
     */
    public function getMetadataBag(): MetadataBag
    {
        return $this->session->getMetadataBag();
    }
}
