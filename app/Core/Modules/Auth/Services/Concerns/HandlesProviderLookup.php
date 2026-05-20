<?php

namespace Flute\Core\Modules\Auth\Services\Concerns;

use Flute\Core\Exceptions\SocialNotFoundException;

trait HandlesProviderLookup
{
    public function getAll(bool $onlyAllowed = true): array
    {
        return $onlyAllowed ? $this->getAllowedProviders() : $this->registeredProviders;
    }

    public function getAllProviders(bool $onlyAllowed = true): array
    {
        return $this->getAll($onlyAllowed);
    }

    public function isEmpty(): bool
    {
        return empty($this->registeredProviders);
    }

    public function toDisplay(): array
    {
        $result = [];

        foreach ($this->getAll() as $provider) {
            $key = $provider['entity']->key;
            if ($key === 'HttpsSteam') {
                $result['Steam'] = $provider['entity']->icon;
            } else {
                $result[$key] = $provider['entity']->icon;
            }
        }

        return $result;
    }

    /**
     * @throws SocialNotFoundException
     */
    public function retrieveSocialNetwork(string $socialNetworkName): array
    {
        if (isset($this->registeredProviders[$socialNetworkName])) {
            return $this->registeredProviders[$socialNetworkName];
        }

        foreach ($this->registeredProviders as $key => $provider) {
            if (strcasecmp($key, $socialNetworkName) === 0) {
                return $provider;
            }
        }

        throw new SocialNotFoundException($socialNetworkName);
    }

    protected function getAllowedProviders(): array
    {
        $result = [];

        foreach ($this->registeredProviders as $key => $provider) {
            if ($provider['entity']->allowToRegister === true) {
                $result[$key] = $provider;
            }
        }

        return $result;
    }

    protected function replaceName(string $socialName)
    {
        return $this->normalizeProviderName($socialName);
    }
}
