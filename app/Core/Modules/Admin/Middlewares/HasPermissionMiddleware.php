<?php

namespace Flute\Core\Modules\Admin\Middlewares;

use Closure;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;

class HasPermissionMiddleware extends BaseMiddleware
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

    public function handle(FluteRequest $request, Closure $next, ...$args): \Symfony\Component\HttpFoundation\Response
    {
        $permissions = array_unique(self::$permission);
        self::$permission = [];

        if (user()->can('admin.boss')) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            abort_unless(user()->can($permission), 403, __('def.no_access'));
        }

        return $next($request);
    }
}
