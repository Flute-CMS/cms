<?php

namespace Flute\Core\Modules\Notifications\Contracts;

/**
 * Interface for modules to provide their notification templates.
 * 
 * Modules implement this interface to register their notification templates
 * that can be customized by administrators in the admin panel.
 */
interface NotificationTemplateProviderInterface
{
    /**
     * Get the notification templates provided by this module.
     * 
     * Each template should be an array with the following structure:
     * [
     *     'key' => 'module.template_name',           // Required: Unique template key
     *     'title' => 'Notification Title: {var}',    // Required: Title with variables
     *     'content' => 'Message with {variables}',   // Required: Content with variables
     *     'icon' => 'ph.bold.icon-name',             // Optional: Phosphor icon
     *     'layout' => 'standard',                    // Optional: Layout type
     *     'variables' => [                           // Required: Available variables
     *         'var' => 'Description of variable',
     *         'variables' => 'Description',
     *     ],
     *     'components' => [...],                     // Optional: Rich components
     *     'channels' => ['inapp', 'email'],          // Optional: Delivery channels
     *     'priority' => 100,                         // Optional: Sort priority
     * ]
     * 
     * @return array<int, array{
     *     key: string,
     *     title: string,
     *     content: string,
     *     icon?: string|null,
     *     layout?: string,
     *     variables: array<string, string>,
     *     components?: array|null,
     *     channels?: array<string>|null,
     *     priority?: int
     * }>
     */
    public function getNotificationTemplates(): array;

    /**
     * Get the module name for template grouping.
     * 
     * @return string Module name (e.g., 'Shop', 'BattlePass')
     */
    public function getModuleName(): string;
}
