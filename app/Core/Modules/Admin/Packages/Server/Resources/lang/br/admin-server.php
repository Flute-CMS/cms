<?php

return [
    'search_servers' => 'Pesquisar Servidores',
    'title' => [
        'list'           => 'Servidores',
        'edit'           => 'Editar Servidor',
        'create'         => 'Adicionar Servidor',
        'description'    => 'Todos os servidores adicionados ao Flute estão listados aqui',
        'main_info'      => 'Informação Principal',
        'actions'        => 'Ações',
        'actions_description'=> 'Ações no servidor',
        'integrations'   => 'Integrações',
    ],

    'tabs' => [
        'main'           => 'Principal',
        'db_connections' => 'Conexões DB',
    ],

    'fields' => [
        'name' => [
            'label'       => 'Nome',
            'placeholder' => 'Digite o nome do servidor',
        ],
        'ip' => [
            'label'       => 'Endereço IP',
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
            'help'        => 'Endereço IP mostrado aos usuários',
        ],
        'ranks' => [
            'label'       => 'Pacote de Rank',
            'placeholder' => 'Selecionar pacote de rank',
        ],
        'ranks_format' => [
            'label'       => 'Formato de Arquivo do Rank',
            'placeholder' => 'Selecionar formato do arquivo rank',
        ],
        'enabled' => [
            'label' => 'Habilitado',
            'help'  => 'O servidor deve estar visível na lista pública',
        ],
        'created_at' => 'Criado em',
    ],

    'status' => [
        'active'   => 'Ativo',
        'inactive' => 'Inativo',
    ],

    'db_connection' => [
        'title' => 'Conexões DB',
        'fields' => [
            'mod' => [
                'label'       => 'Mod',
                'placeholder' => 'Inserir Mod',
                'help'        => 'Selecione o plugin a ser usado neste servidor',
            ],
            'dbname' => [
                'label'       => 'Banco de Dados',
                'placeholder' => 'Insira o nome do banco de dados',
            ],
            'driver' => [
                'label'       => 'Driver',
                'placeholder' => 'Selecionar driver',
                'custom'      => 'Personalizado',
            ],
            'additional' => [
                'label'       => 'Configurações Adicionais',
                'placeholder' => 'Insira configurações adicionais',
            ],
            'params' => 'Param.',
            'custom_driver_name' => [
                'label'       => 'Nome do Driver',
                'placeholder' => 'Digite o nome do driver',
            ],
            'json_settings' => [
                'label'       => 'Configurações JSON',
                'placeholder' => 'Insira as configurações em JSON',
                'help'        => 'Insira configurações JSON arbitrárias',
            ],
        ],
        'add' => [
            'title' => 'Adicoinar Conexão DB',
            'button'=> 'Adicionar Conexão',
        ],
        'edit' => [
            'title' => 'Editar Conexão DB',
        ],
        'delete' => [
            'confirm' => 'Tem certeza de que deseja excluir esta conexão?',
        ],
    ],

    'db_drivers' => [
        'default' => [
            'name' => 'Padrão',
            'fields' => [
                'connection' => [
                    'label'       => 'Conexão',
                    'placeholder' => 'Selecionar conexão DB',
                    'help'        => 'Escolha uma conexão de banco de dados da sua configuração',
                ],
                'table_prefix' => [
                    'label'       => 'Prefixo da Tabela',
                    'placeholder' => 'Digite o prefixo da tabela',
                    'help'        => 'Prefixo para as tabelas do banco de dados',
                ],
            ],
        ],
        'statistics' => [
            'name' => 'Estatísticas',
            'fields' => [
                'connection' => [
                    'label'       => 'Conexão',
                    'placeholder' => 'Selecionar conexão do BD',
                    'help'        => 'Escolha uma conexão de banco de dados da sua configuração',
                ],
                'table_prefix' => [
                    'label'       => 'Prefixo da Tabela',
                    'placeholder' => 'Insira o prefixo da tabela',
                    'help'        => 'Prefixo para as tabelas do banco de dados',
                ],
                'player_table' => [
                    'label'       => 'Tabela do Jogador',
                    'placeholder' => 'Inserir nome da tabela do jogador',
                    'help'        => 'Tabela contendo dados do jogador',
                ],
                'steam_id_field' => [
                    'label'       => 'Campo SteamID',
                    'placeholder' => 'Digite o nome do campo SteamID',
                    'help'        => 'Campo contendo SteamID',
                ],
                'name_field' => [
                    'label'       => 'Campos do Nome',
                    'placeholder' => 'Inserir nome do campo nome',
                    'help'        => 'Campo contendo o nome do jogador',
                ],
            ],
        ],
        'no_drivers' => [
            'title'       => 'Nenhum driver de banco de dados disponível',
            'description' => 'Nenhum driver de banco de dados registrado encontrado. Entre em contato com o administrador.',
        ],
    ],

    'mods' => [
        'custom_settings_name' => [
            'title'       => 'Nome do Driver',
            'placeholder' => 'Digite o nome do driver',
        ],
        'custom_settings_json' => [
            'title'       => 'Configurações JSON',
            'placeholder' => 'Digite as configurações JSON',
        ],
        'custom_alert' => [
            'title'       => 'Aviso!',
            'description' => 'É preciso ter cuidado ao inserir configurações personalizadas! Se não tiver certeza, não adicione configurações personalizadas!',
        ],
        'custom' => 'Personalizado',
    ],

    'buttons' => [
        'add'    => 'Adicionar',
        'save'   => 'Salvar',
        'cancel' => 'Cancelar',
        'delete' => 'Deletar',
        'edit'   => 'Editar',
        'actions'=> 'Ações',
    ],

    'messages' => [
        'server_not_found'             => 'Servidor não encontrado.',
        'connection_not_found'         => 'Conexão não encontrada.',
        'save_success'                 => 'Servidor salvo com sucesso.',
        'delete_success'               => 'Servidor deletado com sucesso.',
        'connection_add_success'       => 'Conexão adicionada com sucesso.',
        'connection_update_success'    => 'Conexão atualizada com sucesso.',
        'connection_delete_success'    => 'Conexão deletada com sucesso.',
        'save_server_first'            => 'Por favor, salve o servidor primeiro.',
        'invalid_driver_settings'      => 'Configurações inválidas do driver.',
        'no_permission.manage'         => 'Você não tem permissão para gerenciar servidores.',
        'no_permission.delete'         => 'Você não tem permissão para remover servidores.',
        'invalid_json'                 => 'Formato JSON inválido.',
        'server_deleted'               => 'Servidor removido com sucesso.',
        'server_updated'               => 'Servidor atualizado com sucesso.',
        'server_created'               => 'Servidor criado com sucesso.',
        'save_not_for_db_connections'  => 'O salvamento é somente para informações do servidor principal.',
        'invalid_ip'                   => 'Digite um endereço IP válido sem uma porta.',
    ],

    'confirms' => [
        'delete_server' => 'Tem certeza de que deseja remover este servidor? Esta ação não pode ser desfeita.',
    ],
];
