<?php

namespace Tests\Integration\Router;

use PHPUnit\Framework\TestCase;
use Flute\Core\Router\Router;
use Flute\Core\Support\FluteRequest;
use DI\Container;

class RouterTest extends TestCase
{
    public function testDispatchSimpleRoute() : void
    {
        $router = new Router(new Container());
        $router->get('/hello', function () {
            return response()->make('Hello from route');
        })->name('hello_route');

        $request = FluteRequest::create('/hello', 'GET');
        $response = $router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Hello from route', $response->getContent());
    }

    public function testNotFoundReturns404() : void
    {
        $router = new Router(new Container());
        $request = FluteRequest::create('/not-exists', 'GET');
        $response = $router->dispatch($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('404', $response->getContent());
    }
}
