<?php

return [
    'labels' => [
        'home' => 'Dashboard',
        'welcome_back' => 'Welcome back, :name',
        'current_time' => 'Current Time',
        'period' => 'Period',
    ],

    'descriptions' => [
        'key_metrics' => 'Key metrics and statistics',
        'welcome_message' => 'Welcome to the dashboard',
        'user_registrations' => 'User registration trends',
        'user_registrations_period' => 'User registrations for last :days days',
        'user_registrations_all' => 'User registrations for all time',
        'user_activity' => 'User activity by day',
        'user_engagement' => 'New users vs active users for last 7 days',
        'notifications' => 'Notification stats',
        'activity_by_hour' => 'Activity by hour',
        'system_load' => 'System load',
        'network_traffic' => 'Network traffic',
        'payment_stats' => 'Payment stats for last 7 days',
        'payment_stats_period' => 'Payment stats for last :days days',
        'payment_stats_all' => 'Payment stats for all time',
        'payment_methods' => 'Payment method distribution',
    ],

    'periods' => [
        '7d' => '7 days',
        '30d' => '1 month',
        '90d' => '3 months',
        '180d' => '6 months',
        '365d' => '1 year',
        'all' => 'All time',
    ],

    'metrics' => [
        'total_users' => 'Total Users',
        'active_users' => 'Active Users',
        'online_users' => 'Online Users',
        'new_users_today' => 'New Users Today',
        'total_notifications' => 'Total Notifications',
        'unread_notifications' => 'Unread Notifications',
        'actions_today' => 'Actions Today',
        'active_sessions' => 'Active Sessions',
        'cpu_load' => 'CPU Load',
        'memory_usage' => 'Memory Usage',
        'disk_usage' => 'Disk Usage',
        'network_load' => 'Network Load',
        'total_revenue' => 'Total Revenue',
        'today_revenue' => 'Revenue Today',
        'period_revenue' => 'Period Revenue',
        'avg_payment' => 'Avg Payment',
        'successful_payments' => 'Successful Payments',
        'promo_usage' => 'Promo Code Usage',
        'php_version' => 'PHP Version',
        'server_load' => 'Server Load',
    ],

    'system' => [
        'version' => 'Version',
        'memory_limit' => 'Memory Limit',
        'max_execution_time' => 'Max Execution Time',
        'upload_max_filesize' => 'Upload Max Filesize',
        'post_max_size' => 'POST Max Size',
        'server' => 'Server',
        'software' => 'Software',
        'os' => 'Operating System',
        'hostname' => 'Hostname',
        'database' => 'Database',
        'driver' => 'Driver',
        'debug_mode' => 'Debug Mode',
        'cache_driver' => 'Cache Driver',
        'timezone' => 'Timezone',
        'extensions' => 'PHP Extensions',
    ],

    'charts' => [
        'user_registrations' => 'User Registrations',
        'user_activity' => 'User Activity',
        'notifications' => 'Notifications',
        'unread' => 'Unread',
        'activity_by_hour' => 'Hourly Activity',
        'new_users' => 'New Users',
        'active_users' => 'Active Users',
        'online_users' => 'Online Users',
        'activity' => 'Activity',
        'payment_stats' => 'Payment Stats',
        'payment_methods' => 'Payment Methods',
        'daily_revenue' => 'Daily Revenue',
        'daily_payments' => 'Daily Payments',
    ],

    'widgets' => [
        'recent_users' => 'Recent Registrations',
        'no_recent_users' => 'No registered users yet',
        'system_info' => 'System Info',
    ],

    'tabs' => [
        'main' => 'Main Info',
        'activity' => 'Activity',
        'payments' => 'Payments',
    ],

    'attention' => [
        'updates' => ':count update(s) available',
        'debug' => 'Debug mode is enabled',
        'cron' => 'Server auto-refresh is not running',
        'performance' => ':count optimization(s) recommended',
    ],

    'checklist' => [
        'items' => [
            'logo' => [
                'title' => 'Upload your logo',
                'desc' => 'Replace the default logo with your brand',
            ],
            'smtp' => [
                'title' => 'Configure email',
                'desc' => 'SMTP for notifications and password resets',
            ],
            'social' => [
                'title' => 'Add social login',
                'desc' => 'Steam, Discord or other auth providers',
            ],
            'server' => [
                'title' => 'Add a game server',
                'desc' => 'Stats, online status and server monitoring',
            ],
            'currency' => [
                'title' => 'Add a currency',
                'desc' => 'Required before setting up payment gateways',
            ],
            'payment' => [
                'title' => 'Set up payments',
                'desc' => 'Accept donations and purchases',
            ],
        ],
    ],

    'onboarding' => [
        'next' => 'Next',
        'prev' => 'Back',
        'finish' => 'Got it!',
        'welcome' => [
            'title' => 'Welcome to Flute!',
            'text' => 'Let\'s take a quick tour of the admin panel so you know where everything is.',
        ],
        'sidebar' => [
            'title' => 'Navigation',
            'text' => 'The sidebar is your main navigation. All sections — servers, modules, themes, users — are accessible from here.',
        ],
        'search' => [
            'title' => 'Quick Search',
            'text' => 'Press <kbd>Ctrl+K</kbd> to instantly search across settings, pages and modules.',
        ],
        'settings' => [
            'title' => 'Settings',
            'text' => 'Site name, mail, auth providers, localization — all general settings are grouped here.',
        ],
        'servers' => [
            'title' => 'Servers',
            'text' => 'Connect game servers for online monitoring, stats and management.',
        ],
        'marketplace' => [
            'title' => 'Marketplace',
            'text' => 'Install modules and themes from the marketplace in one click — extend functionality without code.',
        ],
        'checklist' => [
            'title' => 'Setup Checklist',
            'text' => 'Follow this checklist to finish the initial configuration. Items turn green once completed.',
        ],
        'done' => [
            'title' => 'You\'re all set!',
            'text' => 'Start with the checklist on the dashboard and explore at your own pace. You can always find help in the documentation.',
        ],
        'restart' => 'Take a tour',
        'restart_success' => 'Tour will start on next page load',
    ],

    'ioncube' => [
        'perf_title' => 'Do this to significantly boost performance',
        'perf_desc' => 'ionCube scans module code on every request. Specify the full modules directory path in encoded_paths to avoid unnecessary scanning and speed up the panel and site.',
        'ini_title' => 'Line for php.ini',
        'ini_note' => 'Add (or update) in php.ini:',
        'missing_title' => 'ionCube Loader is not installed',
        'missing_desc' => 'Some marketplace modules are encoded and require ionCube.',
        'install_title' => 'Installation guide',
        'download_button' => 'Download to storage',
        'download_success' => 'Loaders downloaded:',
        'download_failed' => 'Download failed',
        'step1_title' => 'Find php.ini',
        'step2_title' => 'Download Loader',
        'step2_hint' => 'Archive contains files for different PHP versions',
        'step3_title' => 'Add to php.ini',
        'step4_title' => 'Restart PHP',
        'step5_title' => 'Verify',
    ],
];
