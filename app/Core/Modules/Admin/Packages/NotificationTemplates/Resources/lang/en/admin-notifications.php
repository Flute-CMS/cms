<?php

return [
    'menu' => [
        'templates' => 'Notification Templates',
    ],

    'title' => [
        'list' => 'Notification Templates',
        'description' => 'Manage notification templates for modules',
        'edit' => 'Edit Template',
        'edit_description' => 'Configure notification content and settings',
    ],

    'fields' => [
        'key' => 'Template Key',
        'module' => 'Module',
        'title' => 'Title',
        'content' => 'Content',
        'icon' => 'Icon',
        'layout' => 'Layout',
        'channels' => 'Delivery Channels',
        'priority' => 'Priority',
        'enabled' => 'Enabled',
        'components' => 'Components (JSON)',
    ],

    'placeholders' => [
        'title' => 'Notification title with {variables}',
        'content' => 'Notification text with {variables}',
    ],

    'hints' => [
        'key' => 'Unique template key used by modules to send notifications',
        'title' => 'Use {variables} for data substitution. Example: {user_name}, {amount}',
        'content' => 'Main notification text. Supports {variables}',
        'icon' => 'Phosphor icon, e.g.: ph.bold.bell-bold',
        'priority' => 'Lower number = higher sort priority',
        'components' => 'JSON structure for additional components (buttons, progress bar, etc.)',
    ],

    'tabs' => [
        'content' => 'Content',
        'appearance' => 'Appearance',
        'channels' => 'Channels',
        'variables' => 'Variables',
        'components' => 'Components',
    ],

    'blocks' => [
        'content' => 'Basic Information',
        'appearance' => 'Appearance',
        'channels' => 'Delivery Channels',
        'channels_description' => 'Select which channels will be used to send this notification',
        'variables' => 'Available Variables',
        'variables_description' => 'These variables can be used in title and content',
        'components' => 'Rich Components',
        'components_description' => 'Additional elements: buttons, progress bar, timer, etc.',
    ],

    'layouts' => [
        'standard' => 'Standard',
        'card' => 'Card',
        'hero' => 'Hero',
        'compact' => 'Compact',
    ],

    'channels' => [
        'inapp' => 'In-App',
        'email' => 'Email',
        'telegram' => 'Telegram',
        'push' => 'Push Notifications',
    ],

    'components' => [
        'text' => 'Text',
        'header' => 'Header',
        'actions' => 'Action Buttons',
        'progress' => 'Progress Bar',
        'rewards' => 'Rewards',
        'countdown' => 'Countdown Timer',
        'code' => 'Code/Promo',
        'image' => 'Image',
        'user' => 'User Card',
        'divider' => 'Divider',
        'callout' => 'Callout Block',
        'stats' => 'Statistics',
    ],

    'actions' => [
        'navigate' => 'Navigate to URL',
        'api' => 'API Request',
        'modal' => 'Open Modal',
        'copy' => 'Copy to Clipboard',
        'download' => 'Download',
        'dismiss' => 'Dismiss',
        'external' => 'External Link',
    ],

    'enable' => 'Enable',
    'disable' => 'Disable',
    'reset' => 'Reset',
    'customized' => 'Customized',

    'bulk' => [
        'enable' => 'Enable Selected',
        'disable' => 'Disable Selected',
    ],

    'confirms' => [
        'delete' => 'Are you sure you want to delete this template?',
        'reset' => 'Reset template to module defaults?',
    ],

    'errors' => [
        'not_found' => 'Template not found',
        'invalid_json' => 'Invalid JSON format',
    ],

    'messages' => [
        'saved' => 'Template saved successfully',
        'deleted' => 'Template deleted',
        'toggled' => 'Template status changed',
        'reset' => 'Template reset to defaults',
        'bulk_enabled' => ':count templates enabled',
        'bulk_disabled' => ':count templates disabled',
        'bulk_deleted' => ':count templates deleted',
    ],
];
