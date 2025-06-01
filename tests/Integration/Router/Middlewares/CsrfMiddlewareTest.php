<?php

namespace Tests\Integration\Router\Middlewares;

use PHPUnit\Framework\TestCase;
use Flute\Core\Router\Router;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Router\Middlewares\CsrfMiddleware;
use Flute\Core\Services\CsrfTokenService;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfMiddlewareTest extends TestCase
{
    private Router $router;
    private CsrfTokenService $csrfService;

    protected function setUp() : void
    {
        parent::setUp();

        $container = $this->createMock(ContainerInterface::class);

        $this->router = new Router($container);
        $this->router->aliasMiddleware('csrf', CsrfMiddleware::class);

        $this->router->post('/submit-form', function () {
            return response()->make('OK');
        })->middleware('csrf');

        $this->csrfService = new CsrfTokenService($this->createMock(CsrfTokenManagerInterface::class));
    }

    public function testPostWithoutCsrfTokenShouldReturn403() : void
    {
        $request = FluteRequest::create('/submit-form', 'POST');
        $response = $this->router->dispatch($request);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('csrf_expired', $response->getContent());
    }

    public function testPostWithValidTokenShouldPass() : void
    {
        $token = $this->csrfService->getToken();

        $request = FluteRequest::create('/submit-form', 'POST', [
            '_csrf_token' => $token,
        ]);

        $response = $this->router->dispatch($request);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('OK', $response->getContent());
    }
}
