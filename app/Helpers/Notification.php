<?php

use Flute\Core\Modules\Notifications\Services\NotificationService;

if( !function_exists("notification") )
{
    /**
     * Returns the notification service
     * 
     * @return NotificationService
     */
    function notification() : NotificationService
    {
        return app(NotificationService::class);
    }
}
