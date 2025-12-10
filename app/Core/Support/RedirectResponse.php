<?php

namespace Flute\Core\Support;

use Flute\Core\Router\Contracts\RouterInterface;
use Flute\Core\Router\Router;
use Flute\Core\Services\SessionService;
use Nette\Utils\Strings;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse as BaseRedirectResponse;

class RedirectResponse extends BaseRedirectResponse
{
    /**
     * The request instance.
     */
    protected FluteRequest $request;

    /**
     * The session store instance.
     */
    protected SessionService $session;

    /**
     * Route dispatcher instance.
     *
     * @var Router
     */
    protected RouterInterface $router;

    public function __construct(FluteRequest $request, SessionService $session, RouterInterface $routeDispatcher, ?string $to = null, int $status = 302, array $headers = [])
    {
        if ($to === null) {
            $to = $request->getUri();
        }

        parent::__construct($to, $status, $headers);

        $this->request = $request;
        $this->session = $session;
        $this->router = $routeDispatcher;

        if ($request->htmx()->isHtmxRequest()) {
            $this->headers->set('HX-Redirect', $to);
        }
    }

    /**
     * Flash a piece of data to the session.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function with($key, $value = null): RedirectResponse
    {
        $key = is_array($key) ? $key : [$key => $value];

        foreach ($key as $k => $v) {
            $this->session->getFlashBag()->add($k, $v);
        }

        return $this;
    }

    /**
     * Add multiple cookies to the response.
     *
     * @return $this
     */
    public function withCookies(array $cookies): RedirectResponse
    {
        foreach ($cookies as $cookie) {
            $this->headers->setCookie($cookie);
        }

        return $this;
    }

    /**
     * Flash an array of input to the session.
     *
     * @return $this
     */
    public function withInput(?array $input = null): RedirectResponse
    {
        $inputs = !is_null($input) ? $input : $this->request->input();

        foreach ($inputs as $key => $value) {
            $this->session->set("__input_{$key}", $value);
        }

        return $this;
    }

    /**
     * Flash an array of input to the session.
     *
     * @return $this
     */
    public function onlyInput(): RedirectResponse
    {
        return $this->withInput($this->request->only(func_get_args()));
    }

    /**
     * Flash an array of input to the session.
     *
     * @return $this
     */
    public function exceptInput(): RedirectResponse
    {
        return $this->withInput($this->request->except(func_get_args()));
    }

    /**
     * Flash a container of errors to the session.
     *
     * @param  array|string  $values
     *
     * @return $this
     */
    public function withErrors($values): RedirectResponse
    {
        $errors = $this->session->get('error', []);

        if (!is_array($errors)) {
            $errors = [];
        }

        $errors = array_merge($errors, !is_array($values) ? [$values] : $values);

        $this->session->getFlashBag()->set('error', $errors);

        return $this;
    }

    /**
     * Add a fragment identifier to the URL.
     *
     * @return $this
     */
    public function withFragment(string $fragment): RedirectResponse
    {
        return $this->withoutFragment()
            ->setTargetUrl($this->getTargetUrl().'#'.Strings::after($fragment, '#'));
    }

    /**
     * Remove any fragment identifier from the response URL.
     *
     * @return $this
     */
    public function withoutFragment(): RedirectResponse
    {
        return $this->setTargetUrl(Strings::before($this->getTargetUrl(), '#'));
    }

    /**
     * Redirect the user back to their previous location.
     *
     * @return $this
     */
    public function back(int $status = 302, array $headers = []): RedirectResponse
    {
        $targetUrl = $this->sanitizeBackUrl($this->request->headers->get('referer'));

        $this->setTargetUrl($targetUrl)->setStatusCode($status)->headers->add($headers);

        return $this;
    }

    /**
     * Keep back redirects constrained to the current host.
     */
    protected function sanitizeBackUrl(?string $referer): string
    {
        $fallback = config('app.url') ?: '/';

        if (!$referer) {
            return $fallback;
        }

        if (str_starts_with($referer, '/')) {
            return $referer;
        }

        $parsed = parse_url($referer);

        if (!$parsed || empty($parsed['host'])) {
            return $fallback;
        }

        $currentHost = parse_url($this->request->getSchemeAndHttpHost(), PHP_URL_HOST);
        $currentPort = parse_url($this->request->getSchemeAndHttpHost(), PHP_URL_PORT);
        $refererPort = $parsed['port'] ?? null;

        $hostMatches = $currentHost && strcasecmp($parsed['host'], $currentHost) === 0;
        $portMatches = !$refererPort || !$currentPort || (string) $refererPort === (string) $currentPort;

        return $hostMatches && $portMatches ? $referer : $fallback;
    }

    /**
     * Get the request instance.
     */
    public function getRequest(): ?FluteRequest
    {
        return $this->request;
    }

    /**
     * Set the request instance.
     *
     * @return void
     */
    public function setRequest(FluteRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Get the session store instance.
     */
    public function getSession(): ?SessionService
    {
        return $this->session;
    }

    /**
     * Set the session store instance.
     *
     * @return void
     */
    public function setSession(SessionService $session)
    {
        $this->session = $session;
    }

    /**
     * Redirect to a named route.
     *
     * @return $this
     */
    public function route(string $name, array $parameters = [], int $status = 302, array $headers = []): RedirectResponse
    {
        $url = router()->url($name, $parameters);

        $this->setTargetUrl($url)->setStatusCode($status)->headers->add($headers);

        return $this;
    }

    /**
     * Remove all uploaded files form the given input array.
     */
    protected function removeFilesFromInput(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = $this->removeFilesFromInput($value);
            }

            if ($value instanceof SymfonyUploadedFile) {
                unset($input[$key]);
            }
        }

        return $input;
    }
}
