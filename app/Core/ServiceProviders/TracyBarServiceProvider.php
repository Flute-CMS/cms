<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Events\RoutingStartedEvent;
use Flute\Core\Listeners\TracyBarMaintenanceListener;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\TracyBar\FluteTracyBar;

class TracyBarServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            FluteTracyBar::class => \DI\autowire(FluteTracyBar::class),
            'TracyBar' => \DI\get(FluteTracyBar::class),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        if (is_debug() && !is_cli() && !self::isEventStream()) {
            $container->get(FluteTracyBar::class);
        }
    }

    /**
     * Tracy keeps its debug-bar payload in a file that it locks exclusively for
     * the whole request. An SSE response lasts for minutes, so enabling Tracy on
     * one freezes every other request from the same browser (same tracy-session
     * cookie) until the stream ends. A stream renders no bar anyway.
     */
    private static function isEventStream(): bool
    {
        return str_contains((string) ( $_SERVER['HTTP_ACCEPT'] ?? '' ), 'text/event-stream');
    }

    public function getEventListeners(): array
    {
        if (!is_debug() || is_cli()) {
            return [];
        }

        return [
            RoutingStartedEvent::NAME => [
                TracyBarMaintenanceListener::class,
            ],
        ];
    }
}
