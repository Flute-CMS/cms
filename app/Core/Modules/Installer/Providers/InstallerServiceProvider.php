<?php

namespace Flute\Core\Modules\Installer\Providers;

use Flute\Core\Modules\Installer\Services\InstallerConfig;
use Flute\Core\Modules\Installer\Services\InstallerView;
use Flute\Core\Modules\Installer\Services\SystemConfiguration;
use Flute\Core\Modules\Installer\Services\SystemRequirements;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Template\Template;
use Throwable;

class InstallerServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            InstallerConfig::class => \DI\autowire(),
            InstallerView::class => \DI\autowire(),
            SystemRequirements::class => \DI\autowire(),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        if (!is_installed() && !is_cli()) {
            $currentUrl = (string) url()->current();
            if (!str_contains($currentUrl, '/install') && !str_contains($currentUrl, '/live')) {
                die(response()->redirect('/install'));
            }

            app()->setLang(config('lang.locale'));

            try {
                $container->get(\Flute\Core\ServiceProviders\DatabaseServiceProvider::class);
            } catch (Throwable $e) {
                logs('installer')->warning('Installer boot: database not ready yet: ' . $e->getMessage());
            }

            $template = template();

            $this->registerViewComponents($template);
            $this->registerRoutes();

            $template->addNamespace('installer', path('app/Core/Modules/Installer/Resources/views'));
            $template->getTemplateAssets()->getCompiler()->setImportPaths(path('app/Core/Modules/Installer/Resources/assets/sass'));

            (new SystemConfiguration())->initSystem();
        }
    }

    protected function registerViewComponents(Template $template): void
    {
        $componentsDir = path('app/Core/Modules/Installer/Resources/views/components');

        if (is_dir($componentsDir)) {
            $componentFiles = $template->getBladeFiles($componentsDir);

            foreach ($componentFiles as $componentFile) {
                $relativePath = str_replace([$componentsDir.DIRECTORY_SEPARATOR, '.blade.php'], '', $componentFile);
                $alias = str_replace(DIRECTORY_SEPARATOR, '.', $relativePath);

                $componentView = "Core.Modules.Installer.Resources.views.components.".$alias;
                $template->getBlade()->compiler()->component($componentView, $alias);
            }
        }
    }

    /**
     * Register installer routes
     */
    protected function registerRoutes(): void
    {
        router()->registerAttributeRoutes([BASE_PATH.'/app/Core/Modules/Installer/Controllers'], 'Flute\Core\Modules\Installer\Controllers');
    }
}
