<?php

namespace Flute\Core\Services;

use Flute\Core\Modules\Translation\Events\LangChangedEvent;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

class SessionService implements SessionInterface
{
    private Session $session;

    private array $cookieOptions = [];

    private ?FluteRequest $request;

    /**
     * Set once the request explicitly closed the session (long-lived streams do
     * this up front). Re-opening would re-acquire the session file lock and hold
     * it until the response ends, freezing every other request from that browser.
     */
    private bool $closed = false;

    /**
     * SessionService constructor.
     */
    public function __construct(EventDispatcher $eventDispatcher, ?FluteRequest $request = null)
    {
        $this->request = $request;
        $this->cookieOptions = $this->buildCookieOptions();
        $this->applyCookieConfiguration();

        $this->session = new Session();
        $this->setSessionLanguage();

        $this->listen($eventDispatcher);
    }

    /**
     * Listen to change lang event.
     */
    public function listen(EventDispatcher $eventDispatcher): void
    {
        $eventDispatcher->addListener(LangChangedEvent::NAME, [$this, 'onLangChanged']);
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
     * Return flash bang
     */
    public function getFlashBag(): FlashBagInterface
    {
        return $this->session->getFlashBag();
    }

    /**
     * Start the session.
     */
    public function start(): bool
    {
        if ($this->closed) {
            return false;
        }

        if (!$this->session->isStarted()) {
            $this->applyCookieConfiguration();
            $started = $this->session->start();
        } else {
            $started = true;
        }

        if ($started) {
            $this->touchSession();
        }

        return $started;
    }

    /**
     * Set a session value.
     *
     * @param string $name The name of the session variable.
     * @param mixed $value The value to store in the session.
     */
    public function set(string $name, $value): void
    {
        if ($this->closed) {
            return;
        }

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
        if ($this->closed) {
            return $default;
        }

        return $this->session->get($name, $default);
    }

    /**
     * Gets all session values.
     */
    public function all(): array
    {
        return $this->closed ? [] : $this->session->all();
    }

    /**
     * Check if a session variable exists.
     *
     * @param string $name The name of the session variable.
     * @return bool True if the session variable exists, false otherwise.
     */
    public function has(string $name): bool
    {
        return !$this->closed && $this->session->has($name);
    }

    /**
     * Remove a session variable.
     *
     * @param string $name The name of the session variable.
     */
    public function remove(string $name): mixed
    {
        return $this->closed ? null : $this->session->remove($name);
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
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->session->save();
    }

    /**
     * True once save() ran: the data is still readable in memory, but the
     * session must not be re-opened for the rest of this request.
     */
    public function isClosed(): bool
    {
        return $this->closed;
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

    /**
     * Sets the session language
     */
    protected function setSessionLanguage(): void
    {
        $availableLanguages = app('lang.available');
        $defaultLanguage = app('lang.locale');
        $currentCookieLang = cookie()->get('current_lang');
        if (cookie()->has('current_lang') && in_array($currentCookieLang, (array) $availableLanguages, true)) {
            $lang = $currentCookieLang;
        } else {
            $lang = in_array($defaultLanguage, (array) $availableLanguages, true)
                ? $defaultLanguage
                : substr(request()->getPreferredLanguage((array) $availableLanguages), 0, 2);
        }

        app()->setLang($lang);
    }

    /**
     * Configure cookie and session parameters with secure defaults.
     */
    private function applyCookieConfiguration(): void
    {
        $options = $this->cookieOptions;
        $this->applyStorageConfiguration($options);

        session_set_cookie_params([
            'lifetime' => $options['lifetime'],
            'path' => $options['path'],
            'domain' => $options['domain'],
            'secure' => $options['secure'],
            'httponly' => $options['httponly'],
            'samesite' => $options['samesite'],
        ]);

        if (!empty($options['name'])) {
            session_name($options['name']);
        }
    }

    /**
     * Keep app sessions out of the shared PHP session directory and align GC with auth lifetime.
     */
    private function applyStorageConfiguration(array $options): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $savePath = (string) ( config('app.session.save_path') ?: storage_path('framework/sessions') );
        if ($savePath !== '' && !is_dir($savePath)) {
            @mkdir($savePath, 0775, true);
        }

        if ($savePath !== '' && is_dir($savePath) && is_writable($savePath)) {
            ini_set('session.save_path', $savePath);
        }

        $gcMaxLifetime = $this->resolveGcMaxLifetime((int) $options['lifetime']);
        ini_set('session.gc_maxlifetime', (string) $gcMaxLifetime);
    }

    private function resolveGcMaxLifetime(int $sessionLifetime): int
    {
        if ($sessionLifetime > 0) {
            return $sessionLifetime;
        }

        $rememberDuration = config('auth.remember_me') ? config('auth.remember_me_duration') : null;
        if (is_int($rememberDuration) || is_string($rememberDuration) && ctype_digit($rememberDuration)) {
            return max(1440, (int) $rememberDuration);
        }

        return max(1440, (int) ini_get('session.gc_maxlifetime'));
    }

    /**
     * Touch the session to avoid aggressive GC dropping CSRF tokens on some hosts.
     */
    private function touchSession(): void
    {
        if (!$this->session->isStarted()) {
            return;
        }

        $now = time();
        $lastTouch = (int) $this->session->get('_flute_last_activity', 0);
        $gcMaxLifetime = (int) ini_get('session.gc_maxlifetime');

        if ($gcMaxLifetime <= 0) {
            $gcMaxLifetime = 1440;
        }

        $touchInterval = max(30, (int) floor($gcMaxLifetime / 2));

        if ($lastTouch === 0 || ( $now - $lastTouch ) >= $touchInterval) {
            $this->session->set('_flute_last_activity', $now);
        }
    }

    /**
     * Build cookie options from config and request context.
     */
    private function buildCookieOptions(): array
    {
        $sessionConfig = (array) config('app.session', []);
        $appUrl = config('app.url');
        $appScheme = $appUrl ? parse_url($appUrl, PHP_URL_SCHEME) : null;

        $secureFlag = $sessionConfig['secure'] ?? null;
        $secure = is_bool($secureFlag)
            ? $secureFlag
            : ( $this->request?->isSecure() ?? false ) || $appScheme === 'https';

        $sameSite = $sessionConfig['same_site'] ?? 'Lax';
        $allowedSameSite = ['Lax', 'Strict', 'None'];
        $sameSite = in_array($sameSite, $allowedSameSite, true) ? $sameSite : 'Lax';

        $lifetime = $sessionConfig['lifetime'] ?? 0;
        if ((int) $lifetime <= 0 && config('auth.remember_me')) {
            $rememberDuration = config('auth.remember_me_duration');
            if (is_int($rememberDuration) || is_string($rememberDuration) && ctype_digit($rememberDuration)) {
                $lifetime = (int) $rememberDuration;
            }
        }

        return [
            'name' => $sessionConfig['name'] ?? 'flute_session',
            'lifetime' => (int) $lifetime,
            'path' => $sessionConfig['path'] ?? '/',
            'domain' => $sessionConfig['domain'] ?? null,
            'secure' => $secure,
            'httponly' => $sessionConfig['http_only'] ?? true,
            'samesite' => $sameSite,
        ];
    }
}
