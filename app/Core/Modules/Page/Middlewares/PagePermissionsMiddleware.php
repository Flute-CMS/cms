<?php

namespace Flute\Core\Modules\Page\Middlewares;

use Closure;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class PagePermissionsMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, Closure $next, ...$permissions): Response
    {
        $permissions = array_filter(array_map(static fn ($p) => trim((string) $p), $permissions));

        if (empty($permissions)) {
            return $next($request);
        }

        if (user()->can('admin.boss')) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if (user()->can($permission)) {
                return $next($request);
            }
        }

        return $this->error()->forbidden(__('def.access_denied'));
    }
}

