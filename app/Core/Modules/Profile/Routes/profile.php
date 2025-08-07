<?php

namespace Flute\Core\Modules\Profile;

use Flute\Core\Modules\Profile\Controllers\Htmx\ProfileSidebar;
use Flute\Core\Modules\Profile\Controllers\ProfileEditController;
use Flute\Core\Modules\Profile\Controllers\ProfileIndexController;
use Flute\Core\Modules\Profile\Controllers\ProfileRedirectController;
use Flute\Core\Modules\Profile\Controllers\ProfileSocialBindController;
use Flute\Core\Modules\Profile\Controllers\ProfileVerificationController;
use Flute\Core\Modules\Profile\Middlewares\UserExistsMiddleware;
use Flute\Core\Router\Contracts\RouterInterface;

$router->group(['prefix' => "/profile/", 'middleware' => 'auth'], function (RouterInterface $group) {
    $group->get('settings', [ProfileEditController::class, 'index']);

    $group->group(['prefix' => "social/"], function (RouterInterface $socialGroup) {
        $socialGroup->get('bind/{provider}', [ProfileSocialBindController::class, 'bindSocial']);
        $socialGroup->post('unbind/{provider}', [ProfileSocialBindController::class, 'unbindSocial'])->middleware('csrf');
    });

    $group->post('verify-email', [ProfileVerificationController::class, 'verifyEmail'])->middleware('throttle');
});

$router->get('/profile/{id}', [ProfileIndexController::class, 'index'])->middleware(UserExistsMiddleware::class);
$router->get('/profile/{id}/mini', [ProfileIndexController::class, 'mini']);

$router->get('/sidebar/miniprofile', [ProfileSidebar::class, 'open'])->middleware(['htmx', 'auth']);
$router->get('/profile/search/{value}', [ProfileRedirectController::class, 'search'])->middleware('throttle');
