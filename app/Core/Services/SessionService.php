<?php

namespace Flute\Core\Services;

use Flute\Core\Events\LangChangedEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
     * Listen change lang event
     * 
     * @param EventDispatcher $eventDispatcher
     * 
     * @return void
     */
    public function listen(EventDispatcher $eventDispatcher)
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
            $lang = app(LanguageService::class)->getPreferredLanguage();
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
     * @return void
     */
    public function start()
    {
        $this->session->start();
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
    public function get(string $name, $default = null)
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
    public function remove(string $name): void
    {
        $this->session->remove($name);
    }

    /**
     * Clear all session variables.
     */
    public function clear(): void
    {
        $this->session->clear();
    }

    public function getId()
    {
        return $this->session->getId();
    }

    public function setId(string $id)
    {
        $this->session->setId($id);
    }

    public function getName()
    {
        return $this->session->getName();
    }

    public function setName(string $name)
    {
        $this->session->setName($name);
    }

    public function invalidate(?int $lifetime = null)
    {
        return $this->session->invalidate($lifetime);
    }

    public function migrate(bool $destroy = false, ?int $lifetime = null)
    {
        return $this->session->migrate($destroy, $lifetime);
    }

    public function save()
    {
        $this->session->save();
    }

    public function replace(array $attributes)
    {
        $this->session->replace($attributes);
    }

    public function isStarted()
    {
        return $this->session->isStarted();
    }

    public function registerBag(\Symfony\Component\HttpFoundation\Session\SessionBagInterface $bag)
    {
        $this->session->registerBag($bag);
    }

    public function getBag(string $name)
    {
        return $this->session->getBag($name);
    }

    public function getMetadataBag()
    {
        return $this->session->getMetadataBag();
    }
}