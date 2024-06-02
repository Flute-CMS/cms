<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\EventNotification;

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
                    'content' => self::replaceContent($event->content, $eventInstance),
                    'icon' => $event->icon,
                    'url' => $event->url,
                    'title' => $event->title,
                    'user_id' => user()->getCurrentUser()->id,
                    'created_at' => new \DateTime()
                ]);
            }
        }
    }

    private static function replaceContent(string $content, $eventInstance): string
    {
        $content = self::replaceUserContent($content);

        return preg_replace_callback('/\{(.*?)\}/', function ($matches) use ($eventInstance) {
            return self::evaluateExpression($matches[1], $eventInstance);
        }, $content);
    }

    private static function evaluateExpression($expression, $eventInstance)
    {
        if (preg_match('/^(\w+)\((.*?)\)$/', $expression, $matches)) {
            $func = $matches[1];
            $args = self::parseArguments($matches[2]);
            if (function_exists($func)) {
                $result = call_user_func_array($func, $args);
                return is_object($result) ? self::getNestedProperty($result, array_slice(explode('.', $expression), 1)) : $result;
            }
        }

        $parts = explode('.', $expression);
        return self::getNestedProperty($eventInstance, $parts);
    }

    private static function getNestedProperty($object, array $parts)
    {
        $current = $object;

        foreach ($parts as $part) {
            if (preg_match('/(\w+)\((.*?)\)$/', $part, $matches)) {
                $func = $matches[1];
                $args = self::parseArguments($matches[2]);
                if (is_object($current) && method_exists($current, $func)) {
                    $current = call_user_func_array([$current, $func], $args);
                } elseif (function_exists($func)) {
                    $current = call_user_func_array($func, $args);
                } else {
                    return '{' . implode('.', $parts) . '}';
                }
            } elseif (is_object($current)) {
                if (method_exists($current, $part)) {
                    $current = $current->{$part}();
                } elseif (property_exists($current, $part)) {
                    $current = $current->{$part};
                } else {
                    return '{' . implode('.', $parts) . '}';
                }
            } else {
                return '{' . implode('.', $parts) . '}';
            }
        }

        return $current;
    }

    private static function parseArguments($argsString)
    {
        $args = [];
        if (!empty($argsString)) {
            $parts = explode(',', $argsString);
            foreach ($parts as $part) {
                $part = trim($part, " \t\n\r\0\x0B'\"");
                $args[] = $part;
            }
        }
        return $args;
    }

    private static function replaceUserContent(string $content)
    {
        return str_replace(['{name}', '{login}', '{email}', '{balance}'], [
            user()->getCurrentUser()->name,
            user()->getCurrentUser()->login,
            user()->getCurrentUser()->email,
            user()->getCurrentUser()->balance
        ], $content);
    }
}