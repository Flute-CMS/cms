<?php

namespace Flute\Core\Payments;

use Flute\Core\Http\Controllers\Topup\LKApiController;
use Flute\Core\Http\Controllers\Topup\LKViewController;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Http\Middlewares\isAuthenticatedMiddleware;
use Flute\Core\Router\RouteDispatcher;
use Flute\Core\Router\RouteGroup;

class PaymentRoutes
{
    protected RouteDispatcher $router;

    public function __construct(RouteDispatcher $router)
    {
        $this->router = $router;
    }

    public function init()
    {
        $this->router->group(function (RouteGroup $router) {
            $router->middleware(isAuthenticatedMiddleware::class);

            $router->get("", [LKViewController::class, "index"]);

            $router->get("/success", [LKViewController::class, "paymentSuccess"]);
            $router->get("/fail", [LKViewController::class, "paymentFail"]);

        }, '/lk');

        $this->router->group(function (RouteGroup $api) {
            $api->group(function (RouteGroup $apiGroup) {
                $apiGroup->middleware(CSRFMiddleware::class);
                $apiGroup->middleware(isAuthenticatedMiddleware::class);

                $apiGroup->post('/validate-promo', [LKApiController::class, 'validatePromo']);
                $apiGroup->post('/buy/{gateway}', [LKApiController::class, 'purchase']);
                $apiGroup->get('/buy/{gateway}', [LKApiController::class, 'purchase']);
            });

            $api->any('/handle/{gateway}', [LKApiController::class, 'handle']);
        }, '/api/lk');
    }
}