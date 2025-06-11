<?php

namespace Tests\Integration\Router\Middlewares;

use PHPUnit\Framework\TestCase;
use Flute\Core\Router\Router;
use Flute\Core\Router\Middlewares\MaintenanceMiddleware;
use Flute\Core\Support\FluteRequest;

class MaintenanceMiddlewareTest extends TestCase
{
    private Router $router;

    protected function setUp() : void
    {
        parent::setUp();

        config()->set('app.maintenance_mode', true);
        config()->set('installer.finished', true);

        $container = new \DI\Container();
        $container->set(MaintenanceMiddleware::class, new MaintenanceMiddleware());

        $this->router = new Router($container);
        $this->router->middlewareGroup('default', ['maintenance']);
        $this->router->aliasMiddleware('maintenance', MaintenanceMiddleware::class);

        $this->router->get('/dashboard', function () {
            return response()->make('Dashboard');
        })->middleware('maintenance');
    }

    public function testMaintenanceBlocksCommonUser() : void
    {
        // user() — guest => 503
        $req = FluteRequest::create('/dashboard', 'GET');
        $res = $this->router->dispatch($req);

        $this->assertEquals(503, $res->getStatusCode());
        $this->assertStringContainsString('maintenance_mode', $res->getContent());
    }

    public function testAdminAllowed() : void
    {
        $req = FluteRequest::create('/dashboard', 'GET');

        $user = $this->createMock(\Flute\Core\Services\UserService::class);
        $user->method('can')
            ->with('admin')
            ->willReturn(true);

        app()->bind(\Flute\Core\Services\UserService::class, $user);

        $res = $this->router->dispatch($req);

        $this->assertEquals(200, $res->getStatusCode());
        $this->assertEquals('Dashboard', $res->getContent());
    }
}
