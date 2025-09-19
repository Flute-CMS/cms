<?php

return [    'title' => [        'actions' => 'Ações',
    'actions_description' => 'Ações no servidor',
    'integrations' => 'Integrações',
],

    'tabs' => [    ],

    'fields' => [
        'name' => [
            'label' => 'Nome',
            'placeholder' => 'Digite o nome do servidor',
        ],
        'ip' => [
            'label' => 'Endereço IP',
            'placeholder' => '127.0.0.1',
        ],
        'port' => [
            'label' => 'Porta',
            'placeholder' => '27015',
        ],
        'mod' => [
            'label' => 'Jogo',
            'placeholder' => 'Escolhe um jogo',
        ],
        'rcon' => [
            'label' => 'Senha RCON',
            'placeholder' => 'Insira a senha RCON',
            'help' => 'Senha para gerenciamento do servidor remoto',
        ],
        'display_ip' => [
            'label' => 'Exibir IP',
            'placeholder' => '127.0.0.1:27015',
            'help' => 'Endereço IP mostrado aos usuários',
        ],
        'ranks' => [        ],
        'ranks_format' => [        ],
        'enabled' => [        ],    ],

    'status' => [        'inactive' => 'Inativo',
    ],

    'db_connection' => [        'fields' => [
        'mod' => [            ],
        'dbname' => [
            'label' => 'Banco de Dados',
            'placeholder' => 'Insira o nome do banco de dados',
        ],
        'driver' => [            ],
        'additional' => [            ],            'custom_driver_name' => [            ],
        'json_settings' => [            ],
    ],
        'add' => [        ],
        'edit' => [        ],
        'delete' => [        ],
    ],

    'db_drivers' => [
        'default' => [
            'name' => 'Padrão',
            'fields' => [
                'connection' => [                ],
                'table_prefix' => [                ],
            ],
        ],
        'statistics' => [
            'name' => 'Estatísticas',
            'fields' => [
                'connection' => [                ],
                'table_prefix' => [                ],
                'player_table' => [                ],
                'steam_id_field' => [
                    'label' => 'Campo SteamID',
                    'placeholder' => 'Digite o nome do campo SteamID',
                    'help' => 'Campo contendo SteamID',
                ],
                'name_field' => [
                    'label' => 'Campos do Nome',                ],
            ],
        ],
        'no_drivers' => [        ],
    ],

    'mods' => [
        'custom_settings_name' => [        ],
        'custom_settings_json' => [        ],
        'custom_alert' => [        ],    ],

    'buttons' => [        'actions' => 'Ações',
    ],

    'messages' => [        'invalid_ip' => 'Digite um endereço IP válido sem uma porta.',
    ],

    'confirms' => [    ],
];
