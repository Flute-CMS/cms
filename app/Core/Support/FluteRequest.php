<?php

namespace Flute\Core\Support;

use Flute\Core\Database\Entities\User;
use Flute\Core\Exceptions\RequestValidateException;
use Nette\Utils\AssertionException;
use Nette\Utils\Validators;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FluteRequest extends Request
{
    /**
     * Determine if the current request expects a JSON response.
     *
     * @return bool
     */
    public function expectsJson(): bool
    {
        return $this->headers->get('Accept') === 'application/json';
    }

    /**
     * Determine if the current request expects a JSON response.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return $this->headers->get('Content-Type') === 'application/json';
    }

    /**
     * Determine if the current request is an AJAX request.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->headers->get('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Retrieve the referer from the current request.
     *
     * @return string
     */
    public function getReferer(): string
    {
        return $this->headers->get('Referer');
    }

    /**
     * Retrieve the current authenticated user.
     *
     * @return User
     */
    public function user(): User
    {
        return user()->getCurrentUser();
    }

    /**
     * Retrieve the Bearer token from the Authorization header.
     *
     * @return string|null
     */
    public function getAuthorizationBearerToken(): ?string
    {
        $authorizationHeader = $this->headers->get('Authorization');
        if ($authorizationHeader && preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Check if the current request uses a specific method.
     *
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * Check if a specific query parameter exists in the request.
     *
     * @param string $key
     * @return bool
     */
    public function hasQueryParam(string $key): bool
    {
        return $this->query->has($key);
    }

    /**
     * Check if a specific header exists in the request.
     *
     * @param string $key
     * @return bool
     */
    public function hasHeader(string $key): bool
    {
        return $this->headers->has($key);
    }

    /**
     * Получить значение из запроса по ключу или вернуть значение по умолчанию.
     *
     * @param string|null $key
     * @param mixed $default
     * 
     * @return mixed
     */
    public function input(string $key = null, $default = null)
    {
        // Fix for JSON body.
        if ($this->isJson()) {
            $data = json_decode($this->getContent(), true);

            if ($key === null) {
                return $data;
            }

            return $data[$key] ?? $default;
        }

        // Если ключ предоставлен, проверяем данные запроса и GET-параметры.
        if ($key) {
            $value = $this->request->get($key, $this->query->get($key, $default));
            if ($value === $default) {
                $value = $this->attributes->get($key, $default);
            }
            return $value;
        }

        // Собираем все данные, если ключ не предоставлен
        $data = array_merge($this->query->all(), $this->request->all());
        return $data;
    }

    /**
     * Проверить, существует ли определенный ключ в запросе.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->query->has($key) || $this->request->has($key);
    }

    /**
     * Получить значения только для определенных ключей запроса.
     *
     * @param  mixed  $keys
     * @return array
     */
    public function only($keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $results = [];
        foreach ($keys as $key) {
            if ($value = $this->input($key)) {
                $results[$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Проверить, есть ли непустое значение для определенного ключа запроса.
     *
     * @param string $key
     * @return bool
     */
    public function filled(string $key): bool
    {
        return !empty($this->input($key));
    }

    /**
     * Get the session object.
     *
     * @return callable|SessionInterface
     */
    public function session()
    {
        return $this->getSession();
    }

    /**
     * Get the client's IP address.
     *
     * @return string
     */
    public function ip(): string
    {
        return $this->getClientIp();
    }

    /**
     * Получить значения для всех ключей запроса, кроме указанных.
     *
     * @param  mixed  $keys
     * @return array
     */
    public function except($keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $input = $this->input();

        foreach ($keys as $key) {
            unset($input[$key]);
        }

        return $input;
    }

    /**
     * Determine if the request URI matches a specific URI or URL.
     *
     * @param string|null $uri
     * @return bool
     */
    public function is(?string $uri = null): bool
    {
        if (is_null($uri))
            return false;

        if (is_url($uri))
            return $this->getSchemeAndHttpHost() . $this->getPathInfo() === $uri;

        return $this->getPathInfo() === $uri;
    }

    /**
     * Validate the request input.
     *
     * @param array $rules
     * @return array
     * @throws \Exception
     */
    public function validate(array $rules): array
    {
        $validatedData = [];
        $errors = [];

        foreach ($rules as $field => $ruleSet) {
            $value = $this->input($field);
            foreach (explode('|', $ruleSet) as $rule) {
                try {
                    $this->validateRule($value, $rule);
                } catch (AssertionException $e) {
                    dd($e);
                    $errors[$field][] = "The $field field failed validation for rule $rule.";
                }
            }
            if (!isset($errors[$field])) {
                $validatedData[$field] = $value;
            }
        }

        if (count($errors) > 0) {
            throw new RequestValidateException($errors);
        }

        return $validatedData;
    }

    /**
     * Validate individual rule.
     *
     * @param mixed $value
     * @param string $rule
     * 
     * @return void
     */
    private function validateRule($value, string $rule): void
    {
        Validators::assert($value, $rule);
    }

    public function isCsrfValid(): bool
    {
        return template()->getBlade()->csrfIsValid();
    }

    public function __get($name)
    {
        return $this->input($name);
    }
}