<?php

use Flute\Core\Modules\Payments\Controllers\PaymentFormController;
use Flute\Core\Modules\Payments\Controllers\PaymentsApiController;
use Flute\Core\Modules\Payments\Controllers\PaymentsViewController;
use Flute\Core\Router\Contracts\RouterInterface;

$router->group(['middleware' => ['auth', 'site_mode:balance']], static function (RouterInterface $authRouter) {
    $authRouter->get("/lk", [PaymentsViewController::class, "index"])->middleware(config('lk.only_modal', false) ? 'htmx' : null);
    $authRouter->get('/payment/{transaction}', [PaymentsViewController::class, 'processPayment']);

    $authRouter->group(['prefix' => '/api/lk', 'middleware' => ['csrf']], static function (RouterInterface $router) {
        $router->post('/validate-promo', [PaymentFormController::class, 'validatePromo']);
        $router->post('/purchase', [PaymentFormController::class, 'purchase']);
    });
});

$router->get('/lk/success', [PaymentsViewController::class, 'paymentSuccess']);
$router->post('/lk/success', [PaymentsViewController::class, 'paymentSuccess']);
$router->get('/lk/fail', [PaymentsViewController::class, 'paymentFail']);
$router->post('/lk/fail', [PaymentsViewController::class, 'paymentFail']);
$router->get('/ik/success', [PaymentsViewController::class, 'paymentSuccess']);
$router->post('/ik/success', [PaymentsViewController::class, 'paymentSuccess']);
$router->get('/ik/fail', [PaymentsViewController::class, 'paymentFail']);
$router->post('/ik/fail', [PaymentsViewController::class, 'paymentFail']);
$router->post('/api/lk/handle/{gateway}', [PaymentsApiController::class, 'handle']);
