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
        'enabled' => 'Status',
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
        'no_variables' => 'No variables available for this template',
        'usage' => 'Usage',
        'variables_usage' => 'Insert variable into title or content text, e.g.: "Hello, {user_name}!"',
    ],

    'tabs' => [
        'content' => 'Content',
        'settings' => 'Settings',
        'appearance' => 'Appearance',
        'channels' => 'Channels',
        'variables' => 'Variables',
        'components' => 'Components',
    ],

    'blocks' => [
        'content' => 'Basic Information',
        'settings' => 'Settings',
        'appearance' => 'Appearance',
        'channels' => 'Delivery Channels',
        'channels_description' => 'Select which channels will be used to send this notification',
        'variables' => 'Available Variables',
        'variables_description' => 'These variables can be used in title and content',
        'buttons' => 'Action Buttons',
        'buttons_description' => 'Add buttons for quick actions in the notification',
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

    'channels_status' => [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'unavailable' => 'Unavailable',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
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

    'add_button' => 'Add button',
    'buttons_empty' => 'No buttons added',

    'button_fields' => [
        'label' => 'Button label',
        'url' => 'URL',
    ],

    'templates' => [
        'button' => 'Buttons',
        'progress' => 'Progress',
        'countdown' => 'Countdown',
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

    'preview' => [
        'title' => 'Preview',
        'just_now' => 'Just now',
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

    'metrics' => [
        'total_templates' => 'Total Templates',
        'active_templates' => 'Active',
        'modules' => 'Modules',
    ],

    'filters' => [
        'all' => 'All',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'customized' => 'Customized',
    ],
];
