<?php

namespace Flute\Core\Modules\Auth\Services\Concerns;

use Exception;

trait HandlesProviderSettings
{
    private function mapProviderSettings(string $providerKey, array $settings): array
    {
        $settings['keys'] = $this->mapProviderKeys($providerKey, $settings['keys'] ?? []);

        if ($providerKey === 'Vkontakte') {
            $settings['scope'] ??= 'vkid.personal_info email';
        }

        if (!empty($settings['proxy'])) {
            $proxyUrl = $settings['proxy'];
            $parsed = parse_url($proxyUrl);

            $curlProxy = [];

            if (!empty($parsed['user'])) {
                $credentials = $parsed['user'];
                if (!empty($parsed['pass'])) {
                    $credentials .= ':' . $parsed['pass'];
                }
                $curlProxy[CURLOPT_PROXYUSERPWD] = $credentials;

                $scheme = !empty($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
                $host = $parsed['host'] ?? '';
                $port = !empty($parsed['port']) ? ':' . $parsed['port'] : '';
                $proxyUrl = $scheme . $host . $port;
            }

            $curlProxy[CURLOPT_PROXY] = $proxyUrl;

            $settings['curl_options'] = array_replace($settings['curl_options'] ?? [], $curlProxy);
            unset($settings['proxy']);
        }

        return $settings;
    }

    private function normalizeProviderName(string $providerName): string
    {
        $lower = strtolower($providerName);
        if ($lower === 'steam') {
            return 'HttpsSteam';
        }

        if (isset($this->registeredProviders[$providerName])) {
            return $providerName;
        }

        foreach (array_keys($this->registeredProviders) as $registeredKey) {
            if (strcasecmp($registeredKey, $providerName) === 0) {
                return $registeredKey;
            }
        }

        return $providerName;
    }

    private function prepareSettingsPayload(string $providerKey, array $settings): array
    {
        return $this->normalizeSettingsStructure($providerKey, $settings);
    }

    private function normalizeSettingsStructure(string $providerKey, array $settings): array
    {
        $keys = $settings['keys'] ?? [];

        foreach ($this->nonKeySettingFields as $field) {
            if (isset($keys[$field]) && !isset($settings[$field])) {
                $settings[$field] = $keys[$field];
                unset($keys[$field]);
            }
        }

        foreach (['id', 'key', 'client_id', 'clientId'] as $idField) {
            if (isset($settings[$idField]) && !isset($keys['id'])) {
                $keys['id'] = $settings[$idField];
            }
        }

        foreach (['secret', 'client_secret', 'clientSecret'] as $secretField) {
            if (isset($settings[$secretField]) && !isset($keys['secret'])) {
                $keys['secret'] = $settings[$secretField];
            }
        }

        $settings['keys'] = $keys;

        return $settings;
    }

    private function mapProviderKeys(string $providerKey, array $keys): array
    {
        if ($providerKey === 'Twitter' && isset($keys['id'])) {
            $keys['key'] = $keys['id'];
        }

        if (!isset($keys['id']) && isset($keys['key'])) {
            $keys['id'] = $keys['key'];
        }

        return $keys;
    }

    /**
     * @throws Exception
     */
    private function ensureRegistrationAllowed(array $social): void
    {
        if (!$social['entity']->allowToRegister) {
            throw new Exception(__('def.not_found'));
        }
    }

    private function requiresAdditionalRegistration(): bool
    {
        return config('auth.registration.social_supplement');
    }
}
