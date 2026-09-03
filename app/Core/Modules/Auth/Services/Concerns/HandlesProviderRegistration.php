<?php

namespace Flute\Core\Modules\Auth\Services\Concerns;

use Flute\Core\Database\Entities\SocialNetwork;

trait HandlesProviderRegistration
{
    public function registerSocialNetwork(array $config): void
    {
        $socialNetwork = new SocialNetwork();
        $socialNetwork->key = $config['key'];
        $socialNetwork->settings = json_encode($this->prepareSettingsPayload(
            $config['key'],
            $config['settings'] ?? [],
        ));
        $socialNetwork->icon = $config['icon'] ?? '';
        $socialNetwork->enabled = $config['enabled'] ?? true;
        $socialNetwork->allowToRegister = $config['allowToRegister'] ?? true;
        $socialNetwork->cooldownTime = $config['cooldownTime'] ?? 0;

        transaction($socialNetwork)->run();

        $this->registerSocial($socialNetwork);
    }

    public function registerSocials()
    {
        $providers = cache()->callback(
            'flute.social_networks',
            static fn() => SocialNetwork::findAll(['enabled' => true]),
            3600,
        );

        foreach ($providers as $socialNetwork) {
            $this->registerSocial($socialNetwork);
        }
    }

    public function registerSocial(SocialNetwork $socialNetwork)
    {
        $providerName = $this->normalizeProviderName($socialNetwork->key);
        $settings = json_decode($socialNetwork->settings, true) ?? [];

        $settings = $this->normalizeSettingsStructure($socialNetwork->key, $settings);
        $settings = $this->mapProviderSettings($socialNetwork->key, $settings);

        $this->registeredProviders[$providerName] = array_merge([
            'enabled' => true,
            'entity' => $socialNetwork,
        ], $settings);
    }

    public function addSocial(string $key, array $settings, string $icon, bool $enabled = true): SocialNetwork
    {
        $socialNetwork = new SocialNetwork();
        $socialNetwork->key = $key;
        $socialNetwork->settings = json_encode($settings);
        $socialNetwork->icon = $icon;
        $socialNetwork->enabled = $enabled;

        transaction($socialNetwork)->run();

        $this->registerSocial($socialNetwork);

        return $socialNetwork;
    }
}
