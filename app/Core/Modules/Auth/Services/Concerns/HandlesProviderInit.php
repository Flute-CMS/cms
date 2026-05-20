<?php

namespace Flute\Core\Modules\Auth\Services\Concerns;

use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Modules\Auth\Hybrid\Storage\StorageSession;
use Hybridauth\Hybridauth;

trait HandlesProviderInit
{
    private function initializeProviders(): void
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

    private function overrideDefaultProviders(): void
    {
        $this->replaceDiscordProvider();
        $this->initializePSR();
    }

    private function initializePSR()
    {
        $hybridPath =
            BASE_PATH
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'Core'
            . DIRECTORY_SEPARATOR
            . 'Modules'
            . DIRECTORY_SEPARATOR
            . 'Auth'
            . DIRECTORY_SEPARATOR
            . 'Hybrid';

        $loader = app()->getLoader();

        $loader->addPsr4('Hybridauth\\Provider\\', $hybridPath);

        $loader->addClassMap([
            'Hybridauth\\Provider\\Vkontakte' => $hybridPath . DIRECTORY_SEPARATOR . 'Vkontakte.php',
            'Hybridauth\\Provider\\Yandex' => $hybridPath . DIRECTORY_SEPARATOR . 'Yandex.php',
            'Hybridauth\\Provider\\HttpsSteam' => $hybridPath . DIRECTORY_SEPARATOR . 'HttpsSteam.php',
            'Hybridauth\\Provider\\Telegram' => $hybridPath . DIRECTORY_SEPARATOR . 'Telegram.php',
            'Hybridauth\\Provider\\Minecraft' => $hybridPath . DIRECTORY_SEPARATOR . 'Minecraft.php',
        ]);

        $loader->register();
    }

    private function replaceDiscordProvider()
    {
        $loader = app()->getLoader();

        $hybridPath =
            BASE_PATH
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'Core'
            . DIRECTORY_SEPARATOR
            . 'Modules'
            . DIRECTORY_SEPARATOR
            . 'Auth'
            . DIRECTORY_SEPARATOR
            . 'Hybrid';

        $loader->addClassMap([
            'Hybridauth\\Provider\\Discord' => $hybridPath . DIRECTORY_SEPARATOR . 'Discord.php',
        ]);

        $loader->register();
    }

    private function initializeHybridAuth(?string $providerName = null, bool $bind = false): void
    {
        $callbackPath = $bind ? "profile/social/bind/{$providerName}" : "social/{$providerName}";
        $callbackUrl = url($callbackPath)->get();

        $this->hybridauth = new Hybridauth(
            [
                'callback' => $callbackUrl,
                'providers' => $this->registeredProviders,
            ],
            null,
            new StorageSession(),
        );
    }
}
