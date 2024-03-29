<?php

namespace Flute\Core\Installer;

use Flute\Core\Http\Controllers\APIInstallerController;
use Flute\Core\Http\Controllers\ViewInstallerController;
use Flute\Core\Http\Middlewares\InstallerMiddleware;
use Flute\Core\Router\RouteDispatcher;
use Flute\Core\Router\RouteGroup;
use Flute\Core\Template\Template;

class InstallerRoute
{
    protected RouteDispatcher $routeDispatcher;
    protected Template $template;

    public function __construct(RouteDispatcher $routeDispatcher, Template $template)
    {
        $this->routeDispatcher = $routeDispatcher;
        $this->template = $template;
    }

    public function initRoutes()
    {
        $this->routeDispatcher->group(function (RouteGroup $router) {
            $router->middleware(InstallerMiddleware::class);

            $router->get('{id<\d+>}', [ViewInstallerController::class, 'installView']);
            $router->post('{id<\d+>}', [APIInstallerController::class, 'installApi']);
        }, "install/");

        $this->importInstallerView();
    }

    protected function importInstallerView() : void
    {
        $this->template->getBlade()->addInclude('Core/Http/Views/Installer/components/button.blade.php', 'btnInst');
        $this->template->getTemplateAssets()->getCompiler()->addImportPath(path('app/Core/Http/Views/Installer/assets/styles/'));
    }
}