<?php

use Flute\Core\Modules\Notifications\Controllers\Components\NotificationsSidebar;
use Flute\Core\Modules\Notifications\Controllers\NotificationController;
use Flute\Core\Modules\Notifications\Controllers\NotificationSettingsController;
use Flute\Core\Router\Contracts\RouterInterface;

$router->group(['prefix' => "api/notifications", 'middleware' => ['auth', 'site_mode:notifications']], static function (RouterInterface $routeGroup) {
    $routeGroup->get('/all', [NotificationController::class, 'getAll']);
    $routeGroup->get('/unread', [NotificationController::class, 'getUnread']);
    $routeGroup->get('/count-unread', [NotificationController::class, 'getCountUnread']);
    $routeGroup->get('/has-unread', [NotificationController::class, 'hasUnread']);
    $routeGroup->delete('/{id<\d+>}', [NotificationController::class, 'delete']);
    $routeGroup->put('/{id<\d+>}', [NotificationController::class, 'read']);
    $routeGroup->put('/read-all', [NotificationController::class, 'readAll']);
    $routeGroup->delete('/clear', [NotificationController::class, 'clear']);

    $routeGroup->get('/settings', [NotificationSettingsController::class, 'getSettings']);
    $routeGroup->put('/settings/channels', [NotificationSettingsController::class, 'saveChannelSettings']);
    $routeGroup->put('/settings/templates', [NotificationSettingsController::class, 'saveTemplateSettings']);
});

$router->get('sidebar/notifications', [NotificationsSidebar::class, 'sidebarNotifications'])->middleware(['auth', 'htmx', 'site_mode:notifications']);
$router->get('sidebar/notifications/all', [NotificationsSidebar::class, 'all'])->middleware(['auth', 'htmx', 'site_mode:notifications'])->name('notifications.all');
$router->get('sidebar/notifications/all/splitted', [NotificationsSidebar::class, 'allSplitted'])->middleware(['auth', 'htmx', 'site_mode:notifications'])->name('notifications.all.splitted');
$router->get('sidebar/notifications/unread', [NotificationsSidebar::class, 'unread'])->middleware(['auth', 'htmx', 'site_mode:notifications'])->name('notifications.unread');
$router->get('sidebar/notifications/unread/splitted', [NotificationsSidebar::class, 'unreadSplitted'])->middleware(['auth', 'htmx', 'site_mode:notifications'])->name('notifications.unread.splitted');
