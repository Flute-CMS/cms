<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\EventNotification;
use Flute\Core\Database\Entities\Notification;

class EventNotifications
{
    public function listen()
    {
        if( !user()->isLoggedIn() ) return;
        
        $events = rep(EventNotification::class)->findAll();

        foreach ($events as $event) {
            events()->addListener($event->event, function () use ($event) {
                $notification = new Notification;
                $notification->content = $this->replaceContent($event->content);
                $notification->url = $event->url;
                $notification->user = user()->getCurrentUser();
                $notification->icon = $event->icon;
                $notification->title = $event->title;

                notification()->create($notification);
            });
        }
    }

    public function replaceContent(string $content)
    {
        return str_replace(['{name}', '{login}', '{email}', '{balance}'], [
            user()->getCurrentUser()->name,
            user()->getCurrentUser()->login,
            user()->getCurrentUser()->email,
            user()->getCurrentUser()->balance
        ], $content);
    }
}