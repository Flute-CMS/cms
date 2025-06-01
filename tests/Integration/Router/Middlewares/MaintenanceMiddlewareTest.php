<?php

namespace Tests\Integration\Router\Middlewares;

use PHPUnit\Framework\TestCase;
use Flute\Core\Router\Router;
use Flute\Core\Router\Middlewares\MaintenanceMiddleware;
use Flute\Core\Support\FluteRequest;
use Psr\Container\ContainerInterface;

class MaintenanceMiddlewareTest extends TestCase
{
    private Router $router;

    protected function setUp() : void
    {
        parent::setUp();

        config()->set('app.maintenance_mode', true);

        $this->router = new Router($this->createMock(ContainerInterface::class));
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
        // Имитируем, что user()->can('admin')
        // => middleware должен пропустить
        $req = FluteRequest::create('/dashboard', 'GET');
        // ... mock auth ...

        $res = $this->router->dispatch($req);
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertEquals('Dashboard', $res->getContent());
    }
}
