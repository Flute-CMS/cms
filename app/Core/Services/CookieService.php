<?php

namespace Flute\Core\Services;

use DateTimeImmutable;
use Flute\Core\Events\ResponseEvent;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Service for handling cookies in Flute.
 */
class CookieService
{
    protected FluteRequest $request;

    protected array $cookies = [];

    protected array $localCookies = [];

    /**
     * Class constructor.
     *
     * @param FluteRequest $request Current HTTP request.
     */
    public function __construct(FluteRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Returns the value of the cookie with the specified name.
     *
     * @param string $name Cookie name.
     * @param mixed $default Default value returned if the cookie does not exist.
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        if (isset($this->localCookies[$name])) {
            return $this->localCookies[$name];
        }

        return $this->request->cookies->get($name, $default);
    }

    /**
     * Sets a cookie with the specified parameters.
     *
     * @param string $name Cookie name.
     * @param string $value Cookie value.
     * @param DateTimeImmutable|int|null $expire Cookie lifetime (in seconds) or expiration date.
     * @param string $path Path on the server where the cookie is valid.
     * @param string|null $domain Domain where the cookie is valid.
     * @param bool $httpOnly Flag indicating that the cookie should be accessible only through HTTP requests.
     * @param string $sameSite SameSite attribute for the cookie
     * @param bool|null $secure Whether the cookie should only be sent over HTTPS. If null, auto-detect based on request.
     */
    public function set(string $name, string $value, $expire = null, string $path = '/', ?string $domain = null, bool $httpOnly = true, string $sameSite = 'Strict', ?bool $secure = null): void
    {
        $cookie = new Cookie(
            name: $name,
            value: $value,
            expire: $this->getDateTime($expire),
            path: $path,
            domain: $domain,
            secure: $secure ?? $this->request->isSecure(),
            httpOnly: $httpOnly,
            raw: false,
            sameSite: $sameSite
        );
        $this->cookies[$name] = $cookie;
        $this->localCookies[$name] = $value;
    }

    /**
     * Checks if a cookie with the specified name exists.
     *
     * @param string $name Cookie name.
     */
    public function has(string $name): bool
    {
        return $this->request->cookies->has($name) || isset($this->localCookies[$name]);
    }

    /**
     * Removes a cookie with the specified name.
     *
     * @param string $name Cookie name.
     * @param string $path Path on the server where the cookie is valid.
     * @param string|null $domain Domain where the cookie is valid.
     */
    public function remove(string $name, string $path = '/', ?string $domain = null): void
    {
        $this->set($name, '', (new DateTimeImmutable())->modify("-3600 seconds"), $path, $domain);
        unset($this->localCookies[$name]);
    }

    /**
     * Adds all cookies set during the current request to the response headers.
     *
     * @param ResponseEvent $event Response event object.
     */
    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        foreach ($this->cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }

        $event->setResponse($response);
    }

    /**
     * Returns a valid DateTime for setting the cookie.
     *
     * @param DateTimeImmutable|int|null $expire
     */
    protected function getDateTime($expire): DateTimeImmutable
    {
        if ($expire instanceof DateTimeImmutable) {
            return $expire;
        }

        if (is_int($expire)) {
            return (new DateTimeImmutable())->modify("+{$expire} seconds");
        }

        // Default to 30 days for null or any other type
        return (new DateTimeImmutable())->modify("+30 days");
    }
}
