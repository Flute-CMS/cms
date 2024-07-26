<?php

namespace Flute\Core\Profile;

use Flute\Core\Http\Controllers\Profile\EditController;
use Flute\Core\Http\Controllers\Profile\ImagesController;
use Flute\Core\Http\Controllers\Profile\IndexController;
use Flute\Core\Http\Controllers\Profile\ProfileRedirectController;
use Flute\Core\Http\Controllers\Profile\SocialController;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Http\Middlewares\isAuthenticatedMiddleware;
use Flute\Core\Http\Middlewares\ProfileChangeMiddleware;
use Flute\Core\Http\Middlewares\UserExistsMiddleware;
use Flute\Core\Router\RouteGroup;

class ProfileRoutes
{
    public function register()
    {
        router()->group(function (RouteGroup $group) {
            $group->middleware(CSRFMiddleware::class);

            $group->group(function (RouteGroup $edit) {
                $edit->middleware(isAuthenticatedMiddleware::class);

                $edit->group(function (RouteGroup $editMiddleware) {
                    $editMiddleware->middleware(ProfileChangeMiddleware::class);

                    // Images edit
                    $editMiddleware->add('post', '/banner', [ImagesController::class, 'updateBanner']);
                    $editMiddleware->add('post', '/avatar', [ImagesController::class, 'updateAvatar']);

                    // Detail information edit
                    // $editMiddleware->add('post', '/name', [EditController::class, 'updateName']);
                    $editMiddleware->add('post', '/hidden', [EditController::class, 'updateHidden']);
                    // $editMiddleware->add('post', '/uri', [EditController::class, 'updateUri']);
                    $editMiddleware->add('delete', '/deletedevice', [EditController::class, 'deleteDevice']);
                    // $editMiddleware->add('post', '/password', [EditController::class, 'updatePassword']);
                });

                $edit->add('get', '', [EditController::class, 'index']);
            }, "edit");

            $group->group(function (RouteGroup $removeGroup) {
                $removeGroup->middleware(isAuthenticatedMiddleware::class);

                $removeGroup->delete('banner', [ImagesController::class, 'removeBanner']);
                $removeGroup->delete('avatar', [ImagesController::class, 'removeAvatar']);
            });

            $group->group(function (RouteGroup $socialGroup) {
                $socialGroup->middleware(isAuthenticatedMiddleware::class);

                $socialGroup->get('bind/{provider}', [SocialController::class, 'bindSocial']);
                $socialGroup->get('unbind/{provider}', [SocialController::class, 'unbindSocial']);

                $socialGroup->post('hide/{provider}', [SocialController::class, 'hideSocial']);
            }, "social/");

            $group->add('get', 'search/{value}', [ProfileRedirectController::class, 'search']);
            $group->add('get', '{id}', [IndexController::class, 'index'], [UserExistsMiddleware::class]);
        }, "/profile/");
    }
}