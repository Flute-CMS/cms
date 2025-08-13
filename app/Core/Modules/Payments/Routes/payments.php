<?php

use Flute\Core\Modules\Payments\Controllers\PaymentsApiController;
use Flute\Core\Modules\Payments\Controllers\PaymentsViewController;
use Flute\Core\Router\Contracts\RouterInterface;

$router->group(['middleware' => 'auth'], function (RouterInterface $authRouter) {
    $authRouter->get("/lk", [PaymentsViewController::class, "index"])->middleware(config('lk.only_modal', false) ? 'htmx' : null);
    $authRouter->get('/payment/{transaction}', [PaymentsViewController::class, 'processPayment']);

    $authRouter->group(['prefix' => '/lk'], function (RouterInterface $router) {
        $router->get("/success", [PaymentsViewController::class, "paymentSuccess"]);
        $router->get("/fail", [PaymentsViewController::class, "paymentFail"]);
    });
});

$router->post('/api/lk/handle/{gateway}', [PaymentsApiController::class, 'handle']);
