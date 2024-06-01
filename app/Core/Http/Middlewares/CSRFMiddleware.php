<?php

namespace Flute\Core\Http\Middlewares;

use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;

class CSRFMiddleware extends AbstractMiddleware
{
    public function __invoke(FluteRequest $request, \Closure $next)
    {
        if( !is_installed() ) return $next($request);

        if( user()->isLoggedIn() && user()->getCurrentUser()->id === 'API_ID' )
            return $next($request);

        if( !template()->getBlade()->csrfIsValid() && config('auth.csrf_enabled') )
            return $this->error(__('def.csrf_expired'));

        return $next($request);
    }
}