<?php

namespace Flute\Core\Modules\Notifications\Controllers\Components;

use Flute\Core\Support\BaseController;

class NotificationsSidebar extends BaseController
{
    public function sidebarNotifications()
    {
        return $this->htmxRender('flute::partials.notifications', [
            "countAll" => notification()->countAll(),
            "countUnread" => notification()->countUnread()
        ])->setTriggers('open-right-sidebar');
    }
    public function allSplitted()
    {
        $notifications = notification()->all(true);
        return view('flute::partials.notifications.list', compact('notifications'));
    }

    public function all()
    {
        $notifications = notification()->all();
        return view('flute::partials.notifications.list', compact('notifications'));
    }

    public function unread()
    {
        $notifications = notification()->unread();
        return view('flute::partials.notifications.list', compact('notifications'));
    }

    public function unreadSplitted()
    {
        $notifications = notification()->unread(true);
        return view('flute::partials.notifications.list', compact('notifications'));
    }
}