<?php

use Flute\Core\Admin\Http\Controllers\Api\AdminSearchController;
use Flute\Core\Admin\Http\Controllers\Api\ApiController;
use Flute\Core\Admin\Http\Controllers\Api\CacheController;
use Flute\Core\Admin\Http\Controllers\Api\ComposerController;
use Flute\Core\Admin\Http\Controllers\Api\CurrencyController;
use Flute\Core\Admin\Http\Controllers\Api\DatabasesController;
use Flute\Core\Admin\Http\Controllers\Api\EventsTestingController;
use Flute\Core\Admin\Http\Controllers\Api\Footer\FooterController;
use Flute\Core\Admin\Http\Controllers\Api\Footer\FooterSocialsController;
use Flute\Core\Admin\Http\Controllers\Api\IndexApi;
use Flute\Core\Admin\Http\Controllers\Api\HelperAdminController;
use Flute\Core\Admin\Http\Controllers\Api\MainSettingsController;
use Flute\Core\Admin\Http\Controllers\Api\ModulesController;
use Flute\Core\Admin\Http\Controllers\Api\NavigationController;
use Flute\Core\Admin\Http\Controllers\Api\NotificationsController;
use Flute\Core\Admin\Http\Controllers\Api\PagesController;
use Flute\Core\Admin\Http\Controllers\Api\Payments\PaymentsController;
use Flute\Core\Admin\Http\Controllers\Api\Payments\PaymentsPromoController;
use Flute\Core\Admin\Http\Controllers\Api\RedirectsController;
use Flute\Core\Admin\Http\Controllers\Api\RolesController;
use Flute\Core\Admin\Http\Controllers\Api\ServersController;
use Flute\Core\Admin\Http\Controllers\Api\SocialsController;
use Flute\Core\Admin\Http\Controllers\Api\ThemesController;
use Flute\Core\Admin\Http\Controllers\Api\TranslateController;
use Flute\Core\Admin\Http\Controllers\Api\UpdateController;
use Flute\Core\Admin\Http\Controllers\Api\UsersController;
use Flute\Core\Admin\Http\Controllers\Views\ApiView;
use Flute\Core\Admin\Http\Controllers\Views\ComposerView;
use Flute\Core\Admin\Http\Controllers\Views\CurrenciesView;
use Flute\Core\Admin\Http\Controllers\Views\DatabasesView;
use Flute\Core\Admin\Http\Controllers\Views\EventTestingView;
use Flute\Core\Admin\Http\Controllers\Views\Footer\FooterSocialsView;
use Flute\Core\Admin\Http\Controllers\Views\Footer\FooterView;
use Flute\Core\Admin\Http\Controllers\Views\IndexView;
use Flute\Core\Admin\Http\Controllers\Views\MainSettingsView;
use Flute\Core\Admin\Http\Controllers\Views\ModulesView;
use Flute\Core\Admin\Http\Controllers\Views\NotificationsView;
use Flute\Core\Admin\Http\Controllers\Views\PagesView;
use Flute\Core\Admin\Http\Controllers\Views\Payments\PaymentsPromoView;
use Flute\Core\Admin\Http\Controllers\Views\Payments\PaymentsView;
use Flute\Core\Admin\Http\Controllers\Views\RedirectsView;
use Flute\Core\Admin\Http\Controllers\Views\RolesView;
use Flute\Core\Admin\Http\Controllers\Views\ServersView;
use Flute\Core\Admin\Http\Controllers\Views\SocialsView;
use Flute\Core\Admin\Http\Controllers\Views\ThemesView;
use Flute\Core\Admin\Http\Controllers\Views\TranslatesView;
use Flute\Core\Admin\Http\Controllers\Views\UpdateView;
use Flute\Core\Admin\Http\Controllers\Views\UserBlocksView;
use Flute\Core\Admin\Http\Controllers\Views\UsersView;
use Flute\Core\Admin\Http\Controllers\Views\NavbarView;
use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Router\RouteGroup;

$router->group(function ($router) {
    HasPermissionMiddleware::permission('admin');

    $router->middleware(HasPermissionMiddleware::class);

    $router->group(function (RouteGroup $admin) {
        // $admin->get('', [IndexView::class, 'index']);
        $admin->get('/', [IndexView::class, 'index']);

        $admin->get('/update', [UpdateView::class, 'index']);
        $admin->get('/event_testing', [EventTestingView::class, 'index']);

        $admin->get('/dashboard', [IndexView::class, 'dashboard']);
        $admin->get('/settings', [MainSettingsView::class, 'index']);

        $admin->get('/users_blocks', [UserBlocksView::class, 'index']);

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [ModulesView::class, 'list']);
        }, '/modules');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [ThemesView::class, 'list']);
            $adminModule->get('/edit/{theme}', [ThemesView::class, 'edit']);
        }, '/themes');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [ServersView::class, 'list']);
            $adminModule->get('/add', [ServersView::class, 'add']);
            $adminModule->get('/edit/{id}', [ServersView::class, 'edit']);
        }, '/servers');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [PaymentsView::class, 'list']);
            $adminModule->get('/add', [PaymentsView::class, 'add']);
            $adminModule->get('/payments', [PaymentsView::class, 'payments']);
            $adminModule->get('/edit/{id}', [PaymentsView::class, 'edit']);

            $adminModule->group(function (RouteGroup $promo) {
                $promo->get('/list', [PaymentsPromoView::class, 'list']);
                $promo->get('/add', [PaymentsPromoView::class, 'add']);
                $promo->get('/edit/{id}', [PaymentsPromoView::class, 'edit']);
            }, '/promo');
        }, '/payments');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [SocialsView::class, 'list']);
            $adminModule->get('/add', [SocialsView::class, 'add']);
            $adminModule->get('/edit/{id}', [SocialsView::class, 'edit']);
        }, '/socials');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [UsersView::class, 'list']);
            $adminModule->get('/edit/{id}', [UsersView::class, 'edit']);
        }, '/users');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [RolesView::class, 'list']);
            $adminModule->get('/add', [RolesView::class, 'add']);
            $adminModule->get('/edit/{id}', [RolesView::class, 'edit']);
        }, '/roles');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [PagesView::class, 'list']);
            $adminModule->get('/add', [PagesView::class, 'add']);
            $adminModule->get('/edit/{id}', [PagesView::class, 'edit']);
        }, '/pages');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [NavbarView::class, 'list']);
            $adminModule->get('/add', [NavbarView::class, 'add']);
            $adminModule->get('/edit/{id}', [NavbarView::class, 'edit']);
        }, '/navigation');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [NotificationsView::class, 'list']);
            $adminModule->get('/add', [NotificationsView::class, 'add']);
            $adminModule->get('/edit/{id}', [NotificationsView::class, 'edit']);
        }, '/notifications');
        
        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [ApiView::class, 'list']);
            $adminModule->get('/add', [ApiView::class, 'add']);
            $adminModule->get('/edit/{id}', [ApiView::class, 'edit']);
        }, '/api');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [DatabasesView::class, 'list']);
            $adminModule->get('/add', [DatabasesView::class, 'add']);
            $adminModule->get('/edit/{id}', [DatabasesView::class, 'update']);
        }, '/databases');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [CurrenciesView::class, 'list']);
            $adminModule->get('/add', [CurrenciesView::class, 'add']);
            $adminModule->get('/edit/{id}', [CurrenciesView::class, 'update']);
        }, '/currency');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [RedirectsView::class, 'list']);
            $adminModule->get('/add', [RedirectsView::class, 'add']);
            $adminModule->get('/edit/{id}', [RedirectsView::class, 'update']);
        }, '/redirects');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [ComposerView::class, 'list']);
            $adminModule->get('/add', [ComposerView::class, 'add']);
        }, '/composer');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [TranslatesView::class, 'list']);
        }, '/translate');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/list', [FooterView::class, 'list']);
            $adminModule->get('/add', [FooterView::class, 'add']);
            $adminModule->get('/edit/{id}', [FooterView::class, 'edit']);

            $adminModule->group(function (RouteGroup $adminSocial) {
                $adminSocial->get('/list', [FooterSocialsView::class, 'list']);
                $adminSocial->get('/add', [FooterSocialsView::class, 'add']);
                $adminSocial->get('/edit/{id}', [FooterSocialsView::class, 'edit']);
            }, '/socials');
        }, '/footer');
    }, 'admin');

    $router->group(function (RouteGroup $admin) {
        // $admin->get('/', [IndexApi::class, 'index']);
        $admin->get('/createlog', [MainSettingsController::class, 'createLog']);
        
        $admin->get('/getip', [HelperAdminController::class, 'getIP']);
        $admin->post('/check-steam', [HelperAdminController::class, 'checkSteam']);

        $admin->post('/update', [UpdateController::class, 'update']);
        $admin->get('/check-update', [UpdateController::class, 'check']);

        $admin->get('/search/{value}', [AdminSearchController::class, 'search']);

        $admin->post('/settings/{tab}', [MainSettingsController::class, 'index']);
        
        $admin->post('/event_testing/check', [EventsTestingController::class, 'check']);

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->post('/install', [ModulesController::class, 'installFirst']);

            $adminModule->post('/disable/{key}', [ModulesController::class, 'disable']);
            $adminModule->post('/enable/{key}', [ModulesController::class, 'enable']);
            $adminModule->post('/install/{key}', [ModulesController::class, 'install']);
            $adminModule->post('/update/{key}', [ModulesController::class, 'update']);

            $adminModule->put('/{key}', [ModulesController::class, 'changeSettings']);

            $adminModule->delete('/{key}', [ModulesController::class, 'delete']);
        }, '/modules');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->post('/install', [ThemesController::class, 'installFirst']);

            $adminModule->post('/disable/{key}', [ThemesController::class, 'disable']);
            $adminModule->post('/enable/{key}', [ThemesController::class, 'enable']);
            $adminModule->post('/install/{key}', [ThemesController::class, 'install']);

            $adminModule->put('/{key}', [ThemesController::class, 'changeSettings']);
            $adminModule->put('/variables/{key}', [ThemesController::class, 'changeVariables']);

            $adminModule->delete('/{key}', [ThemesController::class, 'delete']);
        }, '/themes');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->delete('/{id}', [ServersController::class, 'delete']);
            $adminModule->post('/add', [ServersController::class, 'add']);
            $adminModule->put('/{id}', [ServersController::class, 'edit']);
        }, '/servers');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->delete('/{id}', [PaymentsController::class, 'delete']);
            $adminModule->post('/add', [PaymentsController::class, 'add']);
            $adminModule->put('/{id}', [PaymentsController::class, 'edit']);
            $adminModule->post('/disable/{id}', [PaymentsController::class, 'disable']);
            $adminModule->post('/enable/{id}', [PaymentsController::class, 'enable']);

            $adminModule->group(function (RouteGroup $promo) {
                $promo->delete('/{id}', [PaymentsPromoController::class, 'delete']);
                $promo->post('/add', [PaymentsPromoController::class, 'add']);
                $promo->put('/{id}', [PaymentsPromoController::class, 'edit']);
            }, '/promo');
        }, '/payments');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->delete('/{id}', [SocialsController::class, 'delete']);
            $adminModule->post('/add', [SocialsController::class, 'add']);
            $adminModule->put('/{id}', [SocialsController::class, 'edit']);
            $adminModule->post('/disable/{id}', [SocialsController::class, 'disable']);
            $adminModule->post('/enable/{id}', [SocialsController::class, 'enable']);
        }, '/socials');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->post('/add', [RolesController::class, 'add']);
            $adminModule->put('/save-order', [RolesController::class, 'saveOrder']);
            $adminModule->put('/{roleId}', [RolesController::class, 'edit']);
            $adminModule->delete('/{roleId}', [RolesController::class, 'delete']);
        }, '/roles');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->delete('/{id}', [PagesController::class, 'delete']);
            $adminModule->post('/add', [PagesController::class, 'add']);
            $adminModule->put('/{id}', [PagesController::class, 'edit']);
            $adminModule->post('/checkroute', [PagesController::class, 'checkRoute']);
        }, '/pages');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->post('/add', [NavigationController::class, 'add']);
            $adminModule->put('/save-order', [NavigationController::class, 'saveOrder']);
            $adminModule->put('/{id}', [NavigationController::class, 'edit']);
            $adminModule->delete('/{id}', [NavigationController::class, 'delete']);
        }, '/navigation');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->post('/add', [NotificationsController::class, 'add']);
            $adminModule->put('/{id}', [NotificationsController::class, 'edit']);
            $adminModule->delete('/{id}', [NotificationsController::class, 'delete']);
        }, '/notifications');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->post('/add', [DatabasesController::class, 'store']);
            $adminModule->put('/{id}', [DatabasesController::class, 'update']);
            $adminModule->delete('/{id}', [DatabasesController::class, 'delete']);
        }, '/databases');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->post('/add', [CurrencyController::class, 'add']);
            $adminModule->put('/{id}', [CurrencyController::class, 'edit']);
            $adminModule->delete('/{id}', [CurrencyController::class, 'delete']);
        }, '/currency');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->post('/add', [RedirectsController::class, 'add']);
            $adminModule->put('/{id}', [RedirectsController::class, 'edit']);
            $adminModule->delete('/{id}', [RedirectsController::class, 'delete']);
        }, '/redirects');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->delete('/{id}', [ComposerController::class, 'delete']);
            $adminModule->post('/install', [ComposerController::class, 'install']);
            $adminModule->post('/uninstall', [ComposerController::class, 'uninstall']);
            $adminModule->get('/table', [ComposerController::class, 'table']);
        }, '/composer');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->post('/add', [ApiController::class, 'add']);
            $adminModule->delete('/{id}', [ApiController::class, 'delete']);
        }, '/api');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->post('/add', [FooterController::class, 'add']);
            $adminModule->put('/save-order', [FooterController::class, 'saveOrder']);
            $adminModule->put('/{id}', [FooterController::class, 'edit']);
            $adminModule->delete('/{id}', [FooterController::class, 'delete']);

            $adminModule->group(function (RouteGroup $adminSocial) {
                $adminSocial->post('/add', [FooterSocialsController::class, 'add']);
                $adminSocial->put('/{id}', [FooterSocialsController::class, 'edit']);
                $adminSocial->delete('/{id}', [FooterSocialsController::class, 'delete']);
            }, '/socials');
        }, '/footer');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->put('/{id}', [UsersController::class, 'edit']);
            $adminModule->delete('/{id}', [UsersController::class, 'delete']);
            $adminModule->post('/{id}/ban', [UsersController::class, 'ban']);
            $adminModule->post('/{id}/unblock', [UsersController::class, 'unblock']);
            $adminModule->post('/{id}/take-money', [UsersController::class, 'takeMoney']);
            $adminModule->post('/{id}/give-money', [UsersController::class, 'giveMoney']);
        }, '/users');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->get('/all', [CacheController::class, 'all']);
            $adminModule->get('/templates', [CacheController::class, 'template']);
            $adminModule->get('/translations', [CacheController::class, 'translations']);
            $adminModule->get('/styles', [CacheController::class, 'styles']);
        }, '/cache');

        $admin->group(function (RouteGroup $adminModule) {
            $adminModule->post('/change', [TranslateController::class, 'edit']);
        }, '/translate');

        // 
    }, 'admin/api');
});