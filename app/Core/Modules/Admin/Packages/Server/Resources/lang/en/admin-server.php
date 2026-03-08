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
        'db_connections' => 'DB Integrations',
    ],

    'fields' => [
        'name' => [
            'label' => 'Name',
            'placeholder' => 'Enter server name',
        ],
        'ip' => [
            'label' => 'IP Address',
            'placeholder' => '127.0.0.1',
        ],
        'port' => [
            'label' => 'Port',
            'placeholder' => '27015',
        ],
        'mod' => [
            'label' => 'Game',
            'placeholder' => 'Select game',
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
        ],
        'ranks_format' => [
            'label' => 'Rank File Format',
            'placeholder' => 'Select rank file format',
        ],
        'ranks_premier' => [
            'label' => 'Premier Ranks',
            'placeholder' => 'Should the server use premier ranks',
        ],
        'query_port' => [
            'label' => 'Query Port',
            'placeholder' => 'Optional. If empty, uses connection port',
        ],
        'rcon_port' => [
            'label' => 'RCON Port',
            'placeholder' => 'Optional. If empty, uses connection port',
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
        'title' => 'DB Integrations',
        'fields' => [
            'mod' => [
                'label' => 'Integration',
                'placeholder' => 'Select integration',
                'help' => 'Choose an integration (stats, bans, VIP, etc.).',
            ],
            'dbname' => [
                'label' => 'Connection',
                'placeholder' => 'Select connection',
                'help' => 'Created in Settings → Databases.',
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
            'title' => 'Add DB Integration',
            'button' => 'Add Integration',
        ],
        'edit' => [
            'title' => 'Edit DB Integration',
        ],
        'create_db' => [
            'title' => 'No database connections',
            'description' => 'Create a connection first to link an integration.',
            'note' => 'The connection will be available after saving.',
            'button' => 'Create Connection',
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
            'title' => 'Warning!',
            'description' => 'Entering custom settings requires caution! If you are unsure, do not add custom settings!',
        ],
        'custom' => 'Custom',
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
        'title' => 'Cron is not configured!',
        'description' => 'Cron mode is enabled, but there are no execution logs for the last hour. Without a configured cron, servers will not be displayed correctly on the site.',
        'setup_button' => 'How to set up',
        'modal_title' => 'Cron Setup',
        'modal_description' => 'Cron is required to periodically query servers and update their status. Without it, server information (online status, players, map) will not be updated.',
        'step_crontab' => 'Open the crontab editor:',
        'step_add_line' => 'Add the following line:',
        'step_windows' => 'Create a task in Task Scheduler via command prompt (as administrator):',
        'verify_title' => 'Verification',
        'verify_description' => 'To verify, run the command manually:',
    ],

    'confirms' => [
        'delete_server' => 'Are you sure you want to delete this server? This action cannot be undone.',
    ],
];
