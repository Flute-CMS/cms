<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\EventNotification;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class NotificationsView extends AbstractController
{
    public static array $events = [
        'flute.password_reset_completed' => 'password_reset_completed',
        'flute.password_reset_requested' => 'password_reset_requested',
        'flute.social_logged_in' => 'social_logged_in',
        'flute.user_logged_in' => 'user_logged_in',
        'flute.user_registered' => 'user_registered',
        'flute.user_verified' => 'user_verified',
        'flute.shop.buy' => 'shop_buy',
        'payment.failed' => 'payment_failed',
        'payment.success' => 'payment_success'
    ];

    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.notifications');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function list(FluteRequest $request)
    {
        $table = table()->setSelectable(true);

        $table->setPhrases([
            'event' => __('admin.notifications.event'),
            'icon' => __('admin.notifications.icon'),
            'url' => __('admin.notifications.url'),
        ]);

        $table->fromEntity(rep(EventNotification::class)->findAll(), ['content'])->withActions('notifications');

        $table->updateColumn('icon', [
            'clean' => false
        ]);

        return view("Core/Admin/Http/Views/pages/notifications/list", [
            "notifications" => $table->render(),
        ]);
    }

    public function add(FluteRequest $request)
    {
        return view("Core/Admin/Http/Views/pages/notifications/add", [
            'events' => self::$events
        ]);
    }

    public function edit(FluteRequest $request, string $id)
    {
        $notification = rep(EventNotification::class)->findByPK($id);

        if (!$notification)
            return $this->error(__('admin.notifications.not_found'), 404);

        return view("Core/Admin/Http/Views/pages/notifications/edit", [
            "notification" => $notification,
            'events' => self::$events
        ]);
    }
}