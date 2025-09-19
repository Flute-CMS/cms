<?php

use Flute\Core\Modules\Notifications\Controllers\Components\NotificationsSidebar;
use Flute\Core\Modules\Notifications\Controllers\NotificationController;
use Flute\Core\Router\Contracts\RouterInterface;

$router->group(['prefix' => "api/notifications", 'middleware' => 'auth'], static function (RouterInterface $routeGroup) {
    $routeGroup->get('/all', [NotificationController::class, 'getAll']);
    $routeGroup->get('/unread', [NotificationController::class, 'getUnread']);
    $routeGroup->get('/count-unread', [NotificationController::class, 'getCountUnread']);
    $routeGroup->delete('/{id<\d+>}', [NotificationController::class, 'delete']);
    $routeGroup->put('/{id<\d+>}', [NotificationController::class, 'read']);
    $routeGroup->delete('', [NotificationController::class, 'clear']);
});

$router->get('sidebar/notifications', [NotificationsSidebar::class, 'sidebarNotifications'])->middleware(['auth', 'htmx']);
$router->get('sidebar/notifications/all', [NotificationsSidebar::class, 'all'])->middleware(['auth', 'htmx'])->name('notifications.all');
$router->get('sidebar/notifications/all/splitted', [NotificationsSidebar::class, 'allSplitted'])->middleware(['auth', 'htmx'])->name('notifications.all.splitted');
$router->get('sidebar/notifications/unread', [NotificationsSidebar::class, 'unread'])->middleware(['auth', 'htmx'])->name('notifications.unread');
$router->get('sidebar/notifications/unread/splitted', [NotificationsSidebar::class, 'unreadSplitted'])->middleware(['auth', 'htmx'])->name('notifications.unread.splitted');
