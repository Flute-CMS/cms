<?php

return [
    'search_servers' => 'Search Servers',
    'title' => [
        'list' => 'Servers',
        'edit' => 'Edit Server',
        'create' => 'Add Server',
        'description' => 'All servers added to Flute are listed here',
        'main_info' => 'Main Information',
        'actions' => 'Actions',
        'actions_description' => 'Actions on the server',
        'integrations' => 'Integrations',
    ],

    'tabs' => [
        'main' => 'Main',
        'db_connections' => 'Plugins & Stats',
    ],

    'fields' => [
        'name' => [
            'label' => 'Name',
            'placeholder' => 'Enter server name',
        ],
        'ip' => [
            'label' => 'IP Address',
            'placeholder' => '127.0.0.1',
            'help' => 'Server IP without port (e.g. 192.168.1.1)',
        ],
        'port' => [
            'label' => 'Port',
            'placeholder' => '27015',
            'help' => 'Game server port (1–65535)',
        ],
        'mod' => [
            'label' => 'Game',
            'placeholder' => 'Select game',
            'help' => 'Cannot be changed after creation',
        ],
        'rcon' => [
            'label' => 'RCON Password',
            'placeholder' => 'Enter RCON password',
            'help' => 'Password for remote server management',
        ],
        'display_ip' => [
            'label' => 'Display IP',
            'placeholder' => '127.0.0.1:27015',
            'help' => 'IP address shown to users',
        ],
        'ranks' => [
            'label' => 'Rank Pack',
            'placeholder' => 'Select rank pack',
            'help' => 'Rank icon set displayed for players',
        ],
        'ranks_format' => [
            'label' => 'Rank File Format',
            'placeholder' => 'Select rank file format',
            'help' => 'Image format of rank icons in the selected pack',
        ],
        'ranks_premier' => [
            'label' => 'Premier Ranks',
            'placeholder' => 'Should the server use premier ranks',
            'help' => 'CS2 Premier rating system instead of classic ranks',
        ],
        'query_port' => [
            'label' => 'Query Port',
            'placeholder' => 'Optional. If empty, uses connection port',
            'help' => 'Port for server status queries. Leave empty to use main port',
        ],
        'rcon_port' => [
            'label' => 'RCON Port',
            'placeholder' => 'Optional. If empty, uses connection port',
            'help' => 'Port for RCON commands. Leave empty to use main port',
        ],
        'enabled' => [
            'label' => 'Enabled',
            'help' => 'Should the server be visible in the public list',
        ],
        'created_at' => 'Created At',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'online' => 'Server Online',
        'offline' => 'Server Offline',
        'hostname' => 'Hostname',
        'map' => 'Map',
        'players' => 'Players',
        'game' => 'Game',
    ],

    'db_connection' => [
        'title' => 'Plugins & Stats',
        'fields' => [
            'mod' => [
                'label' => 'Data type',
                'placeholder' => 'Select type',
                'help' => 'What data to connect: stats, bans, VIP, etc.',
            ],
            'dbname' => [
                'label' => 'Database',
                'placeholder' => 'Select database',
                'help' => 'Connections are created in Settings → Databases.',
            ],
            'driver' => [
                'label' => 'Driver',
                'placeholder' => 'Select driver',
                'custom' => 'Custom',
            ],
            'additional' => [
                'label' => 'Additional Settings',
                'placeholder' => 'Enter additional settings',
            ],
            'params' => 'Param.',
            'custom_driver_name' => [
                'label' => 'Driver Name',
                'placeholder' => 'Enter driver name',
            ],
            'json_settings' => [
                'label' => 'JSON Settings',
                'placeholder' => 'Enter settings in JSON',
                'help' => 'Enter arbitrary JSON settings',
            ],
        ],
        'add' => [
            'title' => 'Connect plugin data',
            'button' => 'Connect',
            'description' => 'Select what type of data you want to display on the site, and specify the database where the plugin stores it.',
        ],
        'edit' => [
            'title' => 'Edit connection',
        ],
        'steps' => [
            'select_type' => 'Select data type',
            'select_db' => 'Select database',
            'configure' => 'Configure',
        ],
        'create_db' => [
            'title' => 'No databases',
            'description' => 'Add a database first to connect plugin data.',
            'note' => 'The database will be available after saving.',
            'button' => 'Add database',
        ],
        'delete' => [
            'confirm' => 'Are you sure you want to delete this connection?',
        ],
    ],

    'db_drivers' => [
        'default' => [
            'name' => 'Default',
            'fields' => [
                'connection' => [
                    'label' => 'Connection',
                    'placeholder' => 'Select DB connection',
                    'help' => 'Choose a database connection from your config',
                ],
                'table_prefix' => [
                    'label' => 'Table Prefix',
                    'placeholder' => 'Enter table prefix',
                    'help' => 'Prefix for database tables',
                ],
            ],
        ],
        'statistics' => [
            'name' => 'Statistics',
            'fields' => [
                'connection' => [
                    'label' => 'Connection',
                    'placeholder' => 'Select DB connection',
                    'help' => 'Choose a database connection from your config',
                ],
                'table_prefix' => [
                    'label' => 'Table Prefix',
                    'placeholder' => 'Enter table prefix',
                    'help' => 'Prefix for database tables',
                ],
                'player_table' => [
                    'label' => 'Player Table',
                    'placeholder' => 'Enter player table name',
                    'help' => 'Table containing player data',
                ],
                'steam_id_field' => [
                    'label' => 'Steam ID Field',
                    'placeholder' => 'Enter Steam ID field name',
                    'help' => 'Field containing the Steam ID',
                ],
                'name_field' => [
                    'label' => 'Name Field',
                    'placeholder' => 'Enter name field name',
                    'help' => 'Field containing the player name',
                ],
            ],
        ],
        'no_drivers' => [
            'title' => 'No DB Drivers Available',
            'description' => 'No registered database drivers found. Please contact the administrator.',
        ],
    ],

    'mods' => [
        'custom_settings_name' => [
            'title' => 'Driver Name',
            'placeholder' => 'Enter driver name',
        ],
        'custom_settings_json' => [
            'title' => 'Settings JSON',
            'placeholder' => 'Enter JSON settings',
        ],
        'custom_alert' => [
            'title' => 'For advanced users',
            'description' => 'Custom settings are for non-standard plugins. Only use this if you know the settings format for the plugin you need.',
        ],
        'custom' => 'Other',
    ],

    'buttons' => [
        'add' => 'Add',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'actions' => 'Actions',
        'test_connection' => 'Test Connection',
    ],

    'messages' => [
        'server_not_found' => 'Server not found.',
        'connection_not_found' => 'Connection not found.',
        'save_success' => 'Server saved successfully.',
        'delete_success' => 'Server deleted successfully.',
        'connection_add_success' => 'Connection added successfully.',
        'connection_update_success' => 'Connection updated successfully.',
        'connection_delete_success' => 'Connection deleted successfully.',
        'save_server_first' => 'Please save the server first.',
        'invalid_driver_settings' => 'Invalid driver settings.',
        'no_permission.manage' => 'You do not have permission to manage servers.',
        'no_permission.delete' => 'You do not have permission to delete servers.',
        'invalid_json' => 'Invalid JSON format.',
        'server_deleted' => 'Server removed successfully.',
        'server_updated' => 'Server updated successfully.',
        'server_created' => 'Server created successfully.',
        'save_not_for_db_connections' => 'Saving is only for main server info.',
        'invalid_ip' => 'Enter a valid IP address without a port.',
        'connection_success' => 'Successfully connected to the server.',
        'connection_failed' => 'Failed to connect to the server',
        'connection_no_response' => 'Server is not responding to queries.',
    ],

    'cron_warning' => [
        'title' => 'Server auto-refresh is not configured',
        'description' => 'Server information (online status, players, map) is not updating automatically. Set up a background task so data refreshes every minute.',
        'setup_button' => 'Setup instructions',
        'modal_title' => 'Server auto-refresh setup',
        'modal_description' => 'To automatically update server status, you need to configure a background task (cron job). Without it, online status, map and player count will not refresh.',
        'step_crontab' => 'Open the task editor:',
        'step_add_line' => 'Add this line (updates every minute):',
        'step_windows' => 'Create a task via command prompt (as administrator):',
        'verify_title' => 'Verification',
        'verify_description' => 'To verify, run the command manually:',
    ],

    'empty' => [
        'title' => 'No servers yet',
        'sub' => 'Add your first game server to start monitoring',
        'db_connections' => [
            'title' => 'No plugins connected',
            'sub' => 'Connect stats, bans, VIP or other plugin data',
        ],
    ],

    'confirms' => [
        'delete_server' => 'Are you sure you want to delete this server? This action cannot be undone.',
    ],
];
