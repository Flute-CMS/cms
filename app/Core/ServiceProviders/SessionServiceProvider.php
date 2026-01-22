<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Services\SessionService;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

class SessionServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            SessionService::class => \DI\autowire(),
            SessionInterface::class => \DI\get(SessionService::class),
            "session" => \DI\get(SessionService::class),
            CsrfTokenManagerInterface::class => \DI\create(CsrfTokenManager::class)
                ->constructor(
                    \DI\get(UriSafeTokenGenerator::class),
                    \DI\get(SessionTokenStorage::class)
                ),
        ]);
    }

    /**
     * Bootstrap the service provider.
     *
     * This method is called after all services are registered.
     * It is used to boot the SessionService and initialize the language settings.
     */
    public function boot(\DI\Container $container): void
    {
        if (!is_cli()) {
            $container->get(SessionService::class)->start();

            $request = $container->get(FluteRequest::class);
            $session = $container->get(SessionInterface::class);
            if (!$request->hasSession()) {
                $request->setSession($session);
            }

            $requestStack = $container->get(RequestStack::class);
            if ($requestStack->getCurrentRequest() !== $request) {
                $requestStack->push($request);
            }
        }
    }
}
