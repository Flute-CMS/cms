<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Installer\InstallerFinder;
use Flute\Core\Installer\InstallerRoute;
use Flute\Core\Installer\InstallerView;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Support\FluteRequest;


class InstallerServiceProvider extends AbstractServiceProvider
{
    public function register( \DI\ContainerBuilder $containerBuilder ): void
    {
        $containerBuilder->addDefinitions([
            InstallerFinder::class => \DI\create(),
            InstallerRoute::class => \DI\autowire(),
            'installer.view' => \DI\create(InstallerView::class)
        ]);
    }

    public function boot( \DI\Container $container ): void
    {
        $installerFinder = $container->get( InstallerFinder::class );

        // Поместим сюда редирект, т.к. по сути это логичнее
        $container->call(function (FluteRequest $fluteRequest) use ($installerFinder) {
            $this->redirectIfNotInstalled($fluteRequest, $installerFinder);
        });

        if (!$installerFinder->isInstalled()) {
            $installerFinder->setDomain();
            $installerFinder->setLocale();
            $container->get(InstallerRoute::class)->initRoutes();
        }
    }

    /**
     * Redirect if is not installed
     * 
     * @return mixed
     */
    protected function redirectIfNotInstalled( FluteRequest $fluteRequest, InstallerFinder $installerFinder )
    {
        /** @var FluteRequest $request */
        $request = $fluteRequest;

        /** @var InstallerFinder $installer */
        $installer = $installerFinder;

        $step = (int) $installer->config('step');
        $finished = (bool) $installer->isInstalled();

        if (!$finished && strpos($request->getPathInfo(), '/install/') === false)
            return response()->redirect("/install/{$step}", 302, [], true);
    }
}