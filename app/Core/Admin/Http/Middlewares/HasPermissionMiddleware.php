<?php

namespace Flute\Core\Admin\Http\Middlewares;

use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;

class HasPermissionMiddleware extends AbstractMiddleware
{
    protected static ?array $permission = [];

    public static function permission($permission)
    {
        if (is_array($permission)) {
            self::$permission = array_merge($permission, static::$permission);
        } else {
            self::$permission[] = $permission;
        }
    }

    public function __invoke(FluteRequest $request, \Closure $next)
    {
        if (user()->hasPermission('admin.boss'))
            return $next($request);

        foreach (array_unique(self::$permission) as $permission) {
            abort_if(user()->hasPermission($permission), 403, __('def.no_access'));
        }

        return $next($request);
    }
}