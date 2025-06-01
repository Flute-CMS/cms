<?php

use GO\Scheduler;

if (!function_exists('scheduler')) {
    function scheduler() : Scheduler
    {
        return app(Scheduler::class);
    }
}
