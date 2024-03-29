<?php

namespace Flute\Core\Support;

class UrlSupport
{
    protected array $getParams;
    protected string $baseUrl;
    protected ?string $originalPath;

    /**
     * Construct a new UrlSupport instance.
     *
     * @param string|null $path   The base path of the URL.
     * @param array       $params The parameters to include in the URL.
     */
    public function __construct(?string $path = null, array $params = [])
    {
        $this->getParams = $params;

        $this->originalPath = $path;

        if(!empty($path) && is_url($path)) {
            $this->baseUrl = $path;
        } elseif(!empty($path) && $path[0] === '/') {
            $this->baseUrl = config('app.url') . $path;
        } elseif(!empty($path)) {
            $this->baseUrl = sprintf('%s/%s', config('app.url'), $path);
        } else {
            $this->baseUrl = request()->getBaseUrl();
        }
    }

    /**
     * Return the base URL without any parameters.
     *
     * @return string The base URL.
     */
    public function force(): string
    {
        return $this->baseUrl;
    }

    /**
     * Merge the current GET parameters with the instance's parameters.
     * 
     * @return self
     */
    public function withGet(): self
    {
        $this->getParams = array_merge($_GET, $this->getParams);
        return $this;
    }

    /**
     * Add parameters to the URL.
     *
     * @param array $params The parameters to add.
     *
     * @return self
     */
    public function addParams(array $params): self
    {
        $this->getParams = array_merge($this->getParams, $params);
        return $this;
    }

    /**
     * Remove specific parameters from the URL.
     *
     * @param array $keys The keys of the parameters to remove.
     *
     * @return self
     */
    public function removeParams(array $keys): self
    {
        foreach ($keys as $key) {
            unset($this->getParams[$key]);
        }
        return $this;
    }

    /**
     * Get the base URL without any parameters.
     *
     * @return string The base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Set a new base URL.
     *
     * @param string $baseUrl The new base URL.
     *
     * @return self
     */
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Get the current parameters of the URL.
     *
     * @return array The current parameters.
     */
    public function getParams(): array
    {
        return $this->getParams;
    }

    /**
     * Set new parameters for the URL, replacing all existing ones.
     *
     * @param array $params The new parameters.
     *
     * @return self
     */
    public function setParams(array $params): self
    {
        $this->getParams = $params;
        return $this;
    }

    /**
     * Get the original url path
     * 
     * @return ?string
     */
    public function getOriginalPath() : ?string
    {
        return $this->originalPath;
    }

    /**
     * Get result
     * 
     * @return string
     */
    public function get(): string
    {
        return $this->getParams ? sprintf("%s?%s", $this->baseUrl, http_build_query($this->getParams)) : $this->baseUrl;
    }

    /**
     * Convert the UrlSupport instance to a string.
     *
     * @return string The URL as a string, including parameters if they exist.
     */
    public function __toString()
    {
        return $this->get();
    }
}
