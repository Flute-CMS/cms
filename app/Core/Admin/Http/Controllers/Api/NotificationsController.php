<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\EventNotification;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class NotificationsController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.notifications');
        $this->middleware(HasPermissionMiddleware::class);
        $this->middleware(CSRFMiddleware::class);
    }

    public function add(FluteRequest $request)
    {
        $notification = new EventNotification;
        $notification->event = $request->event;
        $notification->icon = $request->icon;
        $notification->title = $request->title;
        $notification->content = $request->content;
        $notification->url = $request->input('url', null);
        $notification->user = user()->getCurrentUser();

        transaction($notification)->run();

        user()->log('events.custom_notification_added', $request->event);

        return $this->success();
    }

    public function delete(FluteRequest $request, string $id)
    {
        $notification = $this->getNotification((int) $id);

        if (!$notification)
            return $this->error(__('admin.notifications.not_found'), 404);

        user()->log('events.custom_notification_deleted', $id);

        transaction($notification, 'delete')->run();

        return $this->success();
    }

    public function edit(FluteRequest $request, string $id)
    {
        $notification = $this->getNotification((int) $id);

        if (!$notification)
            return $this->error(__('admin.notifications.not_found'), 404);

        $notification->event = $request->event;
        $notification->icon = $request->icon;
        $notification->title = $request->title;
        $notification->content = $request->content;
        $notification->url = $request->input('url', null);

        user()->log('events.custom_notification_edited', $id);

        transaction($notification)->run();

        return $this->success();
    }

    protected function getNotification(int $id)
    {
        return rep(EventNotification::class)->findByPK($id);
    }
}