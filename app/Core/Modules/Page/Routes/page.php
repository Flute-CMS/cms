<?php

use Flute\Core\Modules\Page\Controllers\ColorController;
use Flute\Core\Modules\Page\Controllers\PageController;
use Flute\Core\Modules\Page\Controllers\WidgetController;
use Flute\Core\Router\Router;

/*
|--------------------------------------------------------------------------
| Page Module Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the framework and assigned
| to the "admin.pages" middleware group.
|
*/

router()->group(['middleware' => 'can:admin.pages', 'prefix' => 'api/pages/'], function (Router $router) {
    $router->delete('delete-widget/{id}', [WidgetController::class, 'deleteWidget'])
        ->name('pages.deleteWidget');

    $router->post('render-widget', [WidgetController::class, 'renderWidget'])
        ->name('pages.renderWidget');

    $router->post('render-widgets', [WidgetController::class, 'renderWidgets'])
        ->name('pages.renderWidgets');

    $router->post('widgets/settings-form', [WidgetController::class, 'settingsForm'])
        ->name('pages.widgetSettingsForm');

    $router->post('widgets/save-settings', [WidgetController::class, 'saveSettings'])
        ->name('pages.saveWidgetSettings');

    $router->post('widgets/handle-action', [WidgetController::class, 'handleAction'])
        ->name('pages.handleWidgetAction');

    $router->post('widgets/buttons-batch', [WidgetController::class, 'getButtonsBatch'])
        ->name('pages.getWidgetButtonsBatch');

    $router->post('widgets/buttons', [WidgetController::class, 'getButtons'])
        ->name('pages.getWidgetButtons');

    $router->post('save-colors', [ColorController::class, 'saveColors'])
        ->name('pages.saveColors');

    $router->post('save-layout', [WidgetController::class, 'saveLayout'])
        ->name('pages.saveLayout');

    $router->get('get-layout', [WidgetController::class, 'getLayout'])
        ->name('pages.getLayout');
    
    $router->post('save-seo', [PageController::class, 'saveSEO'])
        ->name('pages.saveSEO');

    $router->get('seo', [PageController::class, 'seo'])
        ->name('pages.seo');
});

router()->get('offline', [PageController::class, 'offline'])
    ->name('pages.offline');
