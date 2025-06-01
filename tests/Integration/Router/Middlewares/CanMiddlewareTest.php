<?php

namespace Tests\Integration\Router\Middlewares;

use PHPUnit\Framework\TestCase;
use Flute\Core\Router\Router;
use Flute\Core\Router\Middlewares\CanMiddleware;
use Flute\Core\Support\FluteRequest;
use Psr\Container\ContainerInterface;

class CanMiddlewareTest extends TestCase
{
    private Router $router;

    protected function setUp() : void
    {
        parent::setUp();

        $container = $this->createMock(ContainerInterface::class);
        $this->router = new Router($container);

        // aliasMiddleware
        $this->router->aliasMiddleware('can', CanMiddleware::class);

        $this->router->get('/admin', function () {
            return response()->make('Hello Admin');
        })->middleware('can:admin.panel');
    }

    public function testAccessDeniedIfNoPermission() : void
    {
        // Мок user()->hasPermission() - можно глобально подменить
        // или в ContainerControllerResolver. 
        // Либо тест именно на то, что user() не авторизован -> 401

        $request = FluteRequest::create('/admin', 'GET');
        $response = $this->router->dispatch($request);

        // Предположим, middleware возвращает 401
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testAccessGrantedIfHasPermission() : void
    {
        // Тут нужно имитировать, что user() -> can('admin.panel') == true
        // Либо задать user()->setRoles([...]) etc. 
        // Зависит от того, как ваша auth логика устроена

        $request = FluteRequest::create('/admin', 'GET');
        // Подмешать авторизованного user() c нужной ролью ?

        $response = $this->router->dispatch($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Hello Admin', $response->getContent());
    }
}
