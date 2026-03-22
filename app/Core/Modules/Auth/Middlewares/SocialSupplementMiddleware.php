<?php

namespace Flute\Core\Modules\Auth\Middlewares;

use Closure;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class SocialSupplementMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, Closure $next, ...$args): Response
    {
        if (!config('auth.registration.social_supplement')) {
            return $this->error()->notFound();
        }

        if (user()->isLoggedIn()) {
            return response()->redirect('/');
        }

        return $next($request);
    }
}
