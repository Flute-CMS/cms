<?php

namespace Flute\Core\Listeners;

use Flute\Core\Database\Entities\Redirect;
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
                    $newResponse = redirect($redirect['toUrl'])->send();
                    $event->setResponse($newResponse);

                    return;
                }
            }
        }
    }

    protected static function fetchRedirects(string $uri): array
    {
        $cacheKey = 'flute.redirects.' . md5($uri);

        return cache()->callback($cacheKey, static function () use ($uri) {
            $redirects = Redirect::query()
                ->where('fromUrl', $uri)
                ->load('conditionGroups')
                ->load('conditionGroups.conditions')
                ->fetchAll();

            if (empty($redirects)) {
                return [];
            }

            return array_map(static fn ($r) => [
                'id' => $r->id,
                'toUrl' => $r->toUrl,
                'conditionGroups' => array_map(static fn ($g) => [
                    'conditions' => array_map(static fn ($c) => [
                        'type' => $c->type,
                        'operator' => $c->operator,
                        'value' => $c->value,
                    ], $g->conditions),
                ], $r->conditionGroups),
            ], $redirects);
        }, self::CACHE_TIME);
    }

    private static function checkConditions(array $redirect, FluteRequest $request): bool
    {
        foreach ($redirect['conditionGroups'] as $group) {
            if (self::checkConditionGroup($group, $request)) {
                return true;
            }
        }

        return false;
    }

    private static function checkConditionGroup(array $group, FluteRequest $request): bool
    {
        foreach ($group['conditions'] as $condition) {
            if (!self::checkCondition($condition, $request)) {
                return false;
            }
        }

        return true;
    }

    private static function checkCondition(array $condition, FluteRequest $request): bool
    {
        $value = self::getRequestValue($condition['type'], $condition['value'], $request);

        if (empty($condition['value'])) {
            return true;
        }

        if (empty($value)) {
            return false;
        }

        return match ($condition['operator']) {
            'equals' => $value == $condition['value'],
            'not_equals' => $value != $condition['value'],
            'contains' => str_contains($value, $condition['value']),
            'not_contains' => !str_contains($value, $condition['value']),
            default => false,
        };
    }

    private static function getRequestValue(string $field, string $conditionValue, FluteRequest $request): mixed
    {
        return match ($field) {
            'ip' => $request->getClientIp(),
            'cookie' => $request->cookies->all(),
            'referer' => $request->headers->get('referer'),
            'request_method' => $request->getMethod(),
            'user_agent' => $request->headers->get('user-agent'),
            'header' => $request->headers->get($conditionValue),
            'lang' => app()->getLang(),
            default => null,
        };
    }
}
