<?php

namespace Flute\Core\Http\Middlewares;

use Flute\Core\Installer\InstallerFinder;
use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;

class InstallerMiddleware extends AbstractMiddleware
{
    public function __invoke(FluteRequest $req, \Closure $next)
    {
        $id = (int) $req->input('id');
        
        abort_if(!(($id < 0 || $id > 7) || (bool) app(InstallerFinder::class)->isInstalled() === true), 404);

        if( $id > config('installer.step') ) 
            return $this->error(__("install.last_step_required"));

        return $next($req, $id);
    }
}