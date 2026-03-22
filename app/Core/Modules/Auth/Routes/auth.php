<?php

use Flute\Core\Modules\Auth\Controllers\AuthController;
use Flute\Core\Modules\Auth\Controllers\PasswordResetController;
use Flute\Core\Modules\Auth\Controllers\SocialAuthController;
use Flute\Core\Modules\Auth\Controllers\SocialSupplementController;
use Flute\Core\Modules\Auth\Middlewares\ModalAuthMiddleware;
use Flute\Core\Modules\Auth\Middlewares\RegisterMiddleware;
use Flute\Core\Modules\Auth\Middlewares\SocialSupplementMiddleware;
use Flute\Core\Modules\Auth\Middlewares\StandardAuthMiddleware;
use Flute\Core\Router\Contracts\RouterInterface;

if (config('app.auth_enabled', true)) {
    router()->group(['middleware' => 'guest'], static function (RouterInterface $router) {
        $router->get('/login', [AuthController::class, 'getLogin'])->middleware(ModalAuthMiddleware::class);
        $router->get('/register', [AuthController::class, 'getRegister'])->middleware([
            StandardAuthMiddleware::class,
            RegisterMiddleware::class,
        ]);

        if (config('auth.reset_password')) {
            $router->group([], static function (RouterInterface $router) {
                $router->get('/forgot-password', [PasswordResetController::class, 'getReset']);
                $router->get('/reset/{token}', [PasswordResetController::class, 'getResetWithToken']);
            });
        }
    });
}

$router->get('/social/supplement', [
    SocialSupplementController::class,
    'getPage',
])->middleware(SocialSupplementMiddleware::class);

// Note: This GET route initiates OAuth redirect flow for both login and account binding.
// CSRF protection is provided by the OAuth state parameter validated during the callback.
$router->get('/social/{provider}', [SocialAuthController::class, 'redirectToProvider']);
$router->post('/logout', [AuthController::class, 'logout'])->middleware(['auth', 'csrf']);
$router->get('/confirm/{token}', [AuthController::class, 'getConfirmation']);
$router->get('/confirm-email/{token}', [AuthController::class, 'confirmEmailChange']);
$router->get('api/auth/check', [AuthController::class, 'authCheck'])->name('api.auth.check');
