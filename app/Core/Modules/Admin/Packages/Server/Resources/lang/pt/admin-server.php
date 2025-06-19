<?php

return [
    'search_servers' => 'Search Servers',
    'title' => [
        'list'           => 'Servers',
        'edit'           => 'Edit Server',
        'create'         => 'Add Server',
        'description'    => 'All servers added to Flute are listed here',
        'main_info'      => 'Main Information',
        'actions'        => 'Ações',
        'actions_description'=> 'Ações no servidor',
        'integrations'   => 'Integrations',
    ],

    'tabs' => [
        'main'           => 'Main',
        'db_connections' => 'DB Connections',
    ],

    'fields' => [
        'name' => [
            'label'       => 'Nome',
            'placeholder' => 'Digite o nome do servidor',
        ],
        'ip' => [
            'label'       => 'Endereço de IP',
            'placeholder' => '127.0.0.1',
        ],
        'port' => [
            'label'       => 'Porta',
            'placeholder' => '27015',
        ],
        'mod' => [
            'label'       => 'Jogo',
            'placeholder' => 'Escolhe um jogo',
        ],
        'rcon' => [
            'label'       => 'Senha RCON',
            'placeholder' => 'Insira a senha RCON',
            'help'        => 'Senha para gerenciamento do servidor remoto',
        ],
        'display_ip' => [
            'label'       => 'Exibir IP',
            'placeholder' => '127.0.0.1:27015',
            'help'        => 'IP address shown to users',
        ],
        'ranks' => [
            'label'       => 'Rank Pack',
            'placeholder' => 'Select rank pack',
        ],
        'ranks_format' => [
            'label'       => 'Rank File Format',
            'placeholder' => 'Select rank file format',
        ],
        'enabled' => [
            'label' => 'Enabled',
            'help'  => 'Should the server be visible in the public list',
        ],
        'created_at' => 'Created At',
    ],

    'status' => [
        'active'   => 'Active',
        'inactive' => 'Inactive',
    ],

    'db_connection' => [
        'title' => 'DB Connections',
        'fields' => [
            'mod' => [
                'label'       => 'Mod',
                'placeholder' => 'Enter mod',
                'help'        => 'Select plugin to use for this server',
            ],
            'dbname' => [
                'label'       => 'Banco de Dados',
                'placeholder' => 'Insira o nome do banco de dados',
            ],
            'driver' => [
                'label'       => 'Driver',
                'placeholder' => 'Select driver',
                'custom'      => 'Custom',
            ],
            'additional' => [
                'label'       => 'Additional Settings',
                'placeholder' => 'Enter additional settings',
            ],
            'params' => 'Param.',
            'custom_driver_name' => [
                'label'       => 'Driver Name',
                'placeholder' => 'Enter driver name',
            ],
            'json_settings' => [
                'label'       => 'JSON Settings',
                'placeholder' => 'Enter settings in JSON',
                'help'        => 'Enter arbitrary JSON settings',
            ],
        ],
        'add' => [
            'title' => 'Add DB Connection',
            'button'=> 'Add Connection',
        ],
        'edit' => [
            'title' => 'Edit DB Connection',
        ],
        'delete' => [
            'confirm' => 'Are you sure you want to delete this connection?',
        ],
    ],

    'db_drivers' => [
        'default' => [
            'name' => 'Padrão',
            'fields' => [
                'connection' => [
                    'label'       => 'Connection',
                    'placeholder' => 'Select DB connection',
                    'help'        => 'Choose a database connection from your config',
                ],
                'table_prefix' => [
                    'label'       => 'Table Prefix',
                    'placeholder' => 'Enter table prefix',
                    'help'        => 'Prefix for database tables',
                ],
            ],
        ],
        'statistics' => [
            'name' => 'Estatísticas',
            'fields' => [
                'connection' => [
                    'label'       => 'Connection',
                    'placeholder' => 'Select DB connection',
                    'help'        => 'Choose a database connection from your config',
                ],
                'table_prefix' => [
                    'label'       => 'Table Prefix',
                    'placeholder' => 'Enter table prefix',
                    'help'        => 'Prefix for database tables',
                ],
                'player_table' => [
                    'label'       => 'Player Table',
                    'placeholder' => 'Enter player table name',
                    'help'        => 'Table containing player data',
                ],
                'steam_id_field' => [
                    'label'       => 'Campo SteamID',
                    'placeholder' => 'Digite o nome do campo SteamID',
                    'help'        => 'Campo contendo SteamID',
                ],
                'name_field' => [
                    'label'       => 'Campos do Nome',
                    'placeholder' => 'Enter name field name',
                    'help'        => 'Field containing the player name',
                ],
            ],
        ],
        'no_drivers' => [
            'title'       => 'No DB Drivers Available',
            'description' => 'No registered database drivers found. Please contact the administrator.',
        ],
    ],

    'mods' => [
        'custom_settings_name' => [
            'title'       => 'Driver Name',
            'placeholder' => 'Enter driver name',
        ],
        'custom_settings_json' => [
            'title'       => 'Settings JSON',
            'placeholder' => 'Enter JSON settings',
        ],
        'custom_alert' => [
            'title'       => 'Warning!',
            'description' => 'Entering custom settings requires caution! If you are unsure, do not add custom settings!',
        ],
        'custom' => 'Custom',
    ],

    'buttons' => [
        'add'    => 'Add',
        'save'   => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit'   => 'Edit',
        'actions'=> 'Ações',
    ],

    'messages' => [
        'server_not_found'             => 'Server not found.',
        'connection_not_found'         => 'Connection not found.',
        'save_success'                 => 'Server saved successfully.',
        'delete_success'               => 'Server deleted successfully.',
        'connection_add_success'       => 'Connection added successfully.',
        'connection_update_success'    => 'Connection updated successfully.',
        'connection_delete_success'    => 'Connection deleted successfully.',
        'save_server_first'            => 'Please save the server first.',
        'invalid_driver_settings'      => 'Invalid driver settings.',
        'no_permission.manage'         => 'You do not have permission to manage servers.',
        'no_permission.delete'         => 'You do not have permission to delete servers.',
        'invalid_json'                 => 'Invalid JSON format.',
        'server_deleted'               => 'Server removed successfully.',
        'server_updated'               => 'Server updated successfully.',
        'server_created'               => 'Server created successfully.',
        'save_not_for_db_connections'  => 'Saving is only for main server info.',
        'invalid_ip'                   => 'Enter a valid IP address without a port.',
    ],

    'confirms' => [
        'delete_server' => 'Are you sure you want to delete this server? This action cannot be undone.',
    ],
];
