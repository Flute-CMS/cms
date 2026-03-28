<?php

namespace Flute\Core\Listeners;

use Tracy\Debugger;

class TracyBarMaintenanceListener
{
    public function handle(): void
    {
        $shouldHide = false;

        if (is_installed() && config('app.maintenance_mode')) {
            $debugIps = config('app.debug_ips') ?: [];
            $shouldHide = !user()->isLoggedIn() || !in_array(request()->ip(), $debugIps);
        } elseif (!empty(config('app.debug_ips'))) {
            $shouldHide = user()->isLoggedIn() && !in_array(request()->ip(), config('app.debug_ips'));
        }

        if ($shouldHide) {
            Debugger::$showBar = false;
        }
    }
}
