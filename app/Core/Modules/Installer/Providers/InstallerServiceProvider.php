<?php

namespace Flute\Core\Modules\Installer\Providers;

use Flute\Core\Modules\Installer\Components\AdminUserComponent;
use Flute\Core\Modules\Installer\Components\DatabaseComponent;
use Flute\Core\Modules\Installer\Components\FluteKeyComponent;
use Flute\Core\Modules\Installer\Components\LanguageComponent;
use Flute\Core\Modules\Installer\Components\RequirementsComponent;
use Flute\Core\Modules\Installer\Components\SiteSettingsComponent;
use Flute\Core\Modules\Installer\Components\WelcomeComponent;
use Flute\Core\Modules\Installer\Components\SiteInfoComponent;
use Flute\Core\Modules\Installer\Services\InstallerConfig;
use Flute\Core\Modules\Installer\Services\InstallerView;
use Flute\Core\Modules\Installer\Services\SystemRequirements;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Template\Template;
use Flute\Core\Modules\Installer\Services\SystemConfiguration;

class InstallerServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            InstallerConfig::class => \DI\autowire(),
            InstallerView::class => \DI\autowire(),
            SystemRequirements::class => \DI\autowire(),
        ]);
    }

    public function boot(\DI\Container $container) : void
    {
        if (! is_installed()) {
            if (! str_contains(url()->current(), '/install') && ! str_contains(url()->current(), '/live')) {
                die(response()->redirect('/install'));
            }

            app()->setLang(config('lang.locale'));

            $template = template();

            $this->registerViewComponents($template);
            $this->registerRoutes();

            $this->registerComponents([
                'installer.welcome' => WelcomeComponent::class,
                'installer.language' => LanguageComponent::class,
                'installer.requirements' => RequirementsComponent::class,
                'installer.flute_key' => FluteKeyComponent::class,
                'installer.database' => DatabaseComponent::class,
                'installer.admin' => AdminUserComponent::class,
                'installer.site_info' => SiteInfoComponent::class,
                'installer.site_settings' => SiteSettingsComponent::class,
            ]);

            $template->addNamespace('installer', path('app/Core/Modules/Installer/Resources/views'));
            $template->getTemplateAssets()->getCompiler()->setImportPaths(path('app/Core/Modules/Installer/Resources/assets/sass'));

            (new SystemConfiguration())->initSystem();
        }
    }

    protected function registerViewComponents(Template $template) : void
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
    protected function registerRoutes() : void
    {
        router()->registerAttributeRoutes([BASE_PATH.'/app/Core/Modules/Installer/Controllers'], 'Flute\Core\Modules\Installer\Controllers');
    }
}