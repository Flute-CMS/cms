<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\EventNotification;
use Flute\Core\Support\ContentParser;

class EventNotifications
{
    public function listen()
    {
        if (!user()->isLoggedIn())
            return;

        $events = rep(EventNotification::class)->findAll();

        foreach ($events as $event) {
            events()->addDeferredListener($event->event, [$this, 'handleEvent']);
        }
    }

    public static function handleEvent($eventInstance)
    {
        if ($eventInstance::NAME) {
            $events = rep(EventNotification::class)->select()->where('event', $eventInstance::NAME)->fetchAll();

            foreach ($events as $event) {
                $table = db()->table('notifications');

                $table->insertOne([
                    'content' => ContentParser::replaceContent($event->content, $eventInstance),
                    'icon' => $event->icon,
                    'url' => $event->url,
                    'title' => $event->title,
                    'user_id' => user()->getCurrentUser()->id,
                    'created_at' => new \DateTime()
                ]);
            }
        }
    }
}