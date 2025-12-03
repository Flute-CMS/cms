<?php

namespace Flute\Core\Listeners;

use Flute\Core\Database\Entities\Redirect;
use Flute\Core\Database\Entities\RedirectCondition;
use Flute\Core\Events\RoutingFinishedEvent;
use Flute\Core\Support\FluteRequest;

class RedirectsListener
{
    protected const CACHE_TIME = 3600;

    public static function onRoutingFinished(RoutingFinishedEvent $event)
    {
        $request = request();

        if (!is_installed()) {
            return;
        }

        $uri = $request->getRequestUri();

        $redirects = self::fetchRedirects($uri);

        if ($redirects) {
            foreach ($redirects as $redirect) {
                if (self::checkConditions($redirect, $request)) {
                    $newResponse = redirect($redirect->toUrl)->send();
                    $event->setResponse($newResponse);

                    return;
                }
            }
        }
    }

    protected static function fetchRedirects(string $uri): array
    {
        return Redirect::query()
            ->where('fromUrl', $uri)
            ->load('conditionGroups')
            ->load('conditionGroups.conditions')
            ->fetchAll();
    }

    private static function checkConditions(Redirect $redirect, FluteRequest $request)
    {
        foreach ($redirect->conditionGroups as $group) {
            if (self::checkConditionGroup($group, $request)) {
                return true;
            }
        }

        return false;
    }

    private static function checkConditionGroup($group, FluteRequest $request)
    {
        foreach ($group->getConditions() as $condition) {
            if (!self::checkCondition($condition, $request)) {
                return false;
            }
        }

        return true;
    }

    private static function checkCondition(RedirectCondition $condition, FluteRequest $request)
    {
        $value = self::getRequestValue($condition->type, $condition->value, $request);

        if (empty($condition->value)) {
            return true;
        }

        if (empty($value)) {
            return false;
        }

        switch ($condition->operator) {
            case 'equals':
                return $value == $condition->value;
            case 'not_equals':
                return $value != $condition->value;
            case 'contains':
                return strpos($value, $condition->value) !== false;
            case 'not_contains':
                return strpos($value, $condition->value) === false;
            default:
                return false;
        }
    }

    private static function getRequestValue($field, $conditionValue, FluteRequest $request)
    {
        switch ($field) {
            case 'ip':
                return $request->getClientIp();
            case 'cookie':
                return $request->cookies->all();
            case 'referer':
                return $request->headers->get('referer');
            case 'request_method':
                return $request->getMethod();
            case 'user_agent':
                return $request->headers->get('user-agent');
            case 'header':
                return $request->headers->get($conditionValue);
            case 'lang':
                return app()->getLang();
            default:
                return null;
        }
    }
}
