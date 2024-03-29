<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\EventNotification;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class NotificationsView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.notifcations');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function list(FluteRequest $request)
    {
        $table = table();

        $table->fromEntity(rep(EventNotification::class)->findAll())->withActions('notifications');

        return view("Core/Admin/Http/Views/pages/notifications/list", [
            "notifications" => $table->render(),
        ]);
    }

    public function add(FluteRequest $request)
    {
        return view("Core/Admin/Http/Views/pages/notifications/add");
    }

    public function edit(FluteRequest $request, string $id)
    {
        $notification = rep(EventNotification::class)->findByPK($id);

        if (!$notification)
            return $this->error(__('admin.notifications.not_found'), 404);

        return view("Core/Admin/Http/Views/pages/notifications/edit", [
            "notification" => $notification,
        ]);
    }
}