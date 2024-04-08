<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\EventNotification;
use Flute\Core\Database\Entities\Notification;

class EventNotifications
{
    public function listen()
    {
        if (!user()->isLoggedIn())
            return;

        $events = rep(EventNotification::class)->findAll();

        foreach ($events as $event) {
            events()->addDeferredListener($event->event, function ($eventInstance) use ($event) {
                $notification = new Notification;
                $notification->content = $this->replaceContent($event->content, $eventInstance);
                $notification->url = $event->url;
                $notification->user = user()->getCurrentUser();
                $notification->icon = $event->icon;
                $notification->title = $event->title;

                notification()->create($notification);
            });
        }
    }

    private function replaceContent(string $content, $event)
    {
        $content = $this->replaceUserContent($content);

        return preg_replace_callback('/\{(.*?)\}/', function ($matches) use ($event) {
            $parts = explode('.', $matches[1]);
            if (count($parts) == 2 && method_exists($event, $parts[0])) {
                return $event->{$parts[0]}()->{$parts[1]};
            } elseif (count($parts) == 1 && method_exists($event, $parts[0])) {
                return $event->{$parts[0]}();
            } elseif (property_exists($event, $matches[1])) {
                return $event->{$matches[1]};
            } else {
                return $matches[0];
            }
        }, $content);
    }

    private function replaceUserContent(string $content)
    {
        return str_replace(['{name}', '{login}', '{email}', '{balance}'], [
            user()->getCurrentUser()->name,
            user()->getCurrentUser()->login,
            user()->getCurrentUser()->email,
            user()->getCurrentUser()->balance
        ], $content);
    }
}