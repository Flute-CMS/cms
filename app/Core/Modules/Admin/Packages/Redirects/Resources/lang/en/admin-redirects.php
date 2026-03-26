<?php

return [
    'title' => 'Redirects',
    'description' => 'Manage URL redirects with conditions',

    'fields' => [
        'from_url' => [
            'label' => 'From URL',
            'placeholder' => '/old-page',
            'help' => 'The URL path to redirect from (e.g. /old-page)',
        ],
        'to_url' => [
            'label' => 'To URL',
            'placeholder' => '/new-page',
            'help' => 'The destination URL to redirect to',
        ],
        'conditions' => [
            'label' => 'Conditions',
            'help' => 'Optional conditions that must be met for the redirect to trigger',
        ],
        'condition_type' => [
            'label' => 'Type',
            'placeholder' => 'Select condition type',
        ],
        'condition_operator' => [
            'label' => 'Operator',
            'placeholder' => 'Select operator',
        ],
        'condition_value' => [
            'label' => 'Value',
            'placeholder' => 'Enter value',
        ],
    ],

    'condition_types' => [
        'ip' => 'IP Address',
        'cookie' => 'Cookie',
        'referer' => 'Referer',
        'request_method' => 'HTTP Method',
        'user_agent' => 'User Agent',
        'header' => 'HTTP Header',
        'lang' => 'Language',
    ],

    'operators' => [
        'equals' => 'Equals',
        'not_equals' => 'Not equals',
        'contains' => 'Contains',
        'not_contains' => 'Not contains',
    ],

    'buttons' => [
        'add' => 'Add Redirect',
        'save' => 'Save',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'actions' => 'Actions',
        'add_condition_group' => 'Add condition group',
        'add_condition' => 'Add condition',
        'remove_condition' => 'Remove',
        'clear_cache' => 'Clear Cache',
    ],

    'messages' => [
        'save_success' => 'Redirect saved successfully.',
        'update_success' => 'Redirect updated successfully.',
        'delete_success' => 'Redirect deleted successfully.',
        'not_found' => 'Redirect not found.',
        'cache_cleared' => 'Redirects cache cleared successfully.',
        'route_conflict' => 'Warning: the URL ":url" conflicts with an existing route ":route". The redirect may not work as expected because the route will take priority.',
        'from_url_required' => 'The "From URL" field is required.',
        'to_url_required' => 'The "To URL" field is required.',
        'same_urls' => 'The "From URL" and "To URL" cannot be the same.',
    ],

    'empty' => [
        'title' => 'No redirects yet',
        'sub' => 'Create your first redirect to start managing URL forwarding',
    ],

    'confirms' => [
        'delete' => 'Are you sure you want to delete this redirect? This action cannot be undone.',
    ],

    'table' => [
        'from' => 'From',
        'to' => 'To',
        'conditions' => 'Conditions',
        'actions' => 'Actions',
    ],

    'modal' => [
        'create_title' => 'Create Redirect',
        'edit_title' => 'Edit Redirect',
        'conditions_title' => 'Redirect Conditions',
        'conditions_help' => 'Condition groups use OR logic between groups and AND logic within a group.',
        'group_label' => 'Group :number',
    ],

    'settings' => [
        'title' => 'Settings',
        'cache_time' => [
            'label' => 'Cache Duration (seconds)',
            'help' => 'How long redirect rules are cached. Set 0 to disable caching.',
            'placeholder' => '3600',
        ],
    ],

    'alert' => [
        'route_conflict_title' => 'Route Conflict Detected',
    ],
];
