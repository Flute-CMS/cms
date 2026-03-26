<?php

use Flute\Core\Modules\Notifications\Services\NotificationService;
use Flute\Core\Modules\Notifications\Services\NotificationTemplateService;

if (!function_exists("notification")) {
    /**
     * Returns the notification service
     * 
     * @return NotificationService
     */
    function notification(): NotificationService
    {
        return app(NotificationService::class);
    }
}

if (!function_exists("notification_templates")) {
    /**
     * Returns the notification template service
     * 
     * @return NotificationTemplateService
     */
    function notification_templates(): NotificationTemplateService
    {
        return app(NotificationTemplateService::class);
    }
}

if (!function_exists("notify")) {
    /**
     * Send a notification using a template.
     * 
     * This is a shorthand helper for sending templated notifications.
     * 
     * @param string $templateKey The template key (e.g., 'shop.purchase_success')
     * @param \Flute\Core\Database\Entities\User $user The user to notify
     * @param array $data Variables to substitute in the template
     * @param array|null $channels Override channels (optional)
     * @return bool
     */
    function notify(string $templateKey, $user, array $data = [], ?array $channels = null): bool
    {
        return notification_templates()->send($templateKey, $user, $data, $channels);
    }
}

