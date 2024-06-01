<?php

namespace Flute\Core\Http\Middlewares;

use Flute\Core\Database\Entities\Redirect;
use Flute\Core\Database\Entities\RedirectCondition;
use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class RedirectsMiddleware extends AbstractMiddleware
{
    public function __invoke(FluteRequest $request, \Closure $next)
    {
        if( !is_installed() ) return $next($request);

        $redirect = rep(Redirect::class)->select()->where('fromUrl', $request->getRequestUri())->load('conditionGroups')->load('conditionGroups.conditions')->fetchOne();

        if ($redirect) {
            if ($this->checkConditions($redirect, $request)) {
                return new Response('', Response::HTTP_FOUND, ['Location' => $redirect->getToUrl()]);
            }
        }

        return $next($request);
    }

    private function checkConditions(Redirect $redirect, FluteRequest $request)
    {
        foreach ($redirect->getConditions() as $group) {
            if ($this->checkConditionGroup($group, $request)) {
                return true;
            }
        }
        return false;
    }

    private function checkConditionGroup($group, FluteRequest $request)
    {
        foreach ($group->getConditions() as $condition) {
            if (!$this->checkCondition($condition, $request)) {
                return false;
            }
        }
        return true;
    }

    private function checkCondition(RedirectCondition $condition, FluteRequest $request)
    {
        $value = $this->getRequestValue($condition->getType(), $condition->getValue(), $request);

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

    private function getRequestValue($field, $conditionValue, FluteRequest $request)
    {
        switch ($field) {
            case 'ip':
                return $request->getClientIp();
            case 'cookie':
                return $request->cookies->all();
            // case 'country':
            //     return $this->getCountryFromIp($request->getClientIp());
            case 'referer':
                return $request->headers->get('referer');
            case 'request_method':
                return $request->getMethod();
            case 'user_agent':
                return $request->headers->get('user-agent');
            case 'header':
                return $request->headers->get($conditionValue);
            case 'lang':
                return $request->getPreferredLanguage();
            default:
                return null;
        }
    }

    // private function getCountryFromIp($ip)
    // {
    //     try {
    //         $record = $this->geoIpReader->country($ip);
    //         return $record->country->isoCode;
    //     } catch (\Exception $e) {
    //         return null;
    //     }
    // }
}