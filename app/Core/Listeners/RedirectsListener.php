<?php

namespace Flute\Core\Listeners;

use Flute\Core\Database\Entities\Redirect;
use Flute\Core\Database\Entities\RedirectCondition;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;
use Flute\Core\Events\RoutingFinishedEvent;

class RedirectsListener
{
    public static function onRoutingFinished(RoutingFinishedEvent $event)
    {
        $request = request();

        if (!is_installed()) {
            return;
        }

        $redirects = rep(Redirect::class)->select()->where('fromUrl', $request->getRequestUri())->load('conditionGroups')->load('conditionGroups.conditions')->fetchAll();

        if ($redirects) {
            foreach ($redirects as $redirect) {
                if (self::checkConditions($redirect, $request)) {
                    $newResponse = redirect($redirect->getToUrl())->send();
                    $event->setResponse($newResponse);
                    return;
                }
            }
        }
    }

    private static function checkConditions(Redirect $redirect, FluteRequest $request)
    {
        foreach ($redirect->getConditions() as $group) {
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
        $value = self::getRequestValue($condition->getType(), $condition->getValue(), $request);

        if (empty($condition->getValue())) {
            return true;
        }

        if (empty($value)) {
            return false;
        }

        switch ($condition->getOperator()) {
            case 'equals':
                return $value == $condition->getValue();
            case 'not_equals':
                return $value != $condition->getValue();
            case 'contains':
                return strpos($value, $condition->getValue()) !== false;
            case 'not_contains':
                return strpos($value, $condition->getValue()) === false;
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
