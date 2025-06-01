<?php

use Flute\Core\Modules\Auth\Controllers\AuthController;
use Flute\Core\Modules\Auth\Controllers\PasswordResetController;
use Flute\Core\Modules\Auth\Controllers\SocialAuthController;
use Flute\Core\Modules\Auth\Middlewares\ModalAuthMiddleware;
use Flute\Core\Modules\Auth\Middlewares\StandardAuthMiddleware;
use Flute\Core\Modules\Auth\Middlewares\RegisterMiddleware;
use Flute\Core\Router\Contracts\RouterInterface;

router()->group(['middleware' => 'guest'], function (RouterInterface $router) {
    $router->get('/login', [AuthController::class, 'getLogin'])->middleware(ModalAuthMiddleware::class);
    $router->get('/register', [AuthController::class, 'getRegister'])->middleware([StandardAuthMiddleware::class, RegisterMiddleware::class]);


    $router->group([], function (RouterInterface $router) {
        $router->get('/social/{provider}', [SocialAuthController::class, 'redirectToProvider']);
        $router->get('/social/register', [SocialAuthController::class, 'getSocialRegister']);
    });

    if (config('auth.reset_password')) {
        $router->group([], function (RouterInterface $router) {
            $router->get('/forgot-password', [PasswordResetController::class, 'getReset']);
            $router->get('/forgot-password/{token}', [PasswordResetController::class, 'getResetWithToken']);
        });
    }

    $router->group(['middleware' => StandardAuthMiddleware::class], function (RouterInterface $router) {
        $router->post('/register', [AuthController::class, 'postRegister']);
        $router->post('/login', [AuthController::class, 'postLogin']);

        if (config('auth.reset_password')) {
            $router->post('/reset', [PasswordResetController::class, 'postReset']);
            $router->post('/reset/{token}', [PasswordResetController::class, 'postResetWithToken']);
        }

        $router->post('/social/register', [SocialAuthController::class, 'postSocialRegister']);
    });
});

$router->get('/logout', [AuthController::class, 'getLogout'])->middleware('auth');
$router->get('/confirm/{token}', [AuthController::class, 'getConfirmation']);