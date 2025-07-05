<?php

return [
    'labels' => [
        'main_settings'                      => 'Configurações Principais',        'site_name'                          => 'Nome do Site',
        'site_url'                           => 'URL do Site',
        'timezone'                           => 'Fuso horário',
        'steam_api'                          => 'Chave API STEAM',        'maintenance_mode'                   => 'Modo de Manutenção',
        'maintenance_message'                => 'Mensagem de Manutenção',        'cron_mode'                          => 'Modo CRON',
        'csrf_enabled'                       => 'Tokens CSRF',
        'convert_to_webp'                    => 'Converter Imagens para WebP',
        'debug'                              => 'Modo Debug',
        'debug_ips'                          => 'Debug IPs',
        'currency_view'                      => 'Moeda Exibida',        'copyright'                          => 'Flute Copyright',        'logo'                               => 'Logo',
        'bg_image'                           => 'Imagem de Fundo',
        'reset_password'                     => 'Recuperar Senha',        'confirm_email'                      => 'Confirmação de E-mail',        'default_avatar'                     => 'Avatar Padrão',
        'default_banner'                     => 'Banner Padrão',        'host'                               => 'Host',
        'port'                               => 'Porta',
        'username'                           => 'Nome de usuário',
        'password'                           => 'Senha',        'locale'                             => 'Idioma Padrão',
        'available'                          => 'Idiomas Disponíveis',
        'db_driver'                          => 'Driver do Banco de Dados',
        'database_name'                      => 'Nome do Banco de Dados',
        'user'                               => 'Usuário',
        'database'                           => 'Banco de dados',        'share_description'                  => 'Seu site enviará relatórios de erros aos servidores Flute.',        'main'                               => 'Principal',
        'home'                               => 'Configurações Principais',
        'flute_key'                          => 'Chave Flute',
        'description'                        => 'Descrição do Site',    ],

    'options' => [
        'robots' => [        ],
    ],

    'placeholders' => [        'username'                 => 'Nome de usuário SMTP',
        'password'                 => 'Senha SMTP',        'db_driver'                => 'Selecionar driver do banco de dados',
        'database_name'            => 'Nome do Banco de Dados',
        'db_host'                  => 'Host do Banco de Dados',
        'db_port'                  => '3306',
        'db_user'                  => 'Usuário do Banco de Dados',
        'db_database'              => 'Nome do Banco de Dados',
        'db_password'              => 'Senha do Banco de Dados',    ],

    'buttons' => [
        'clear_cache'          => 'Limpar Cache',
        'save'                 => 'Salvar',
        'add'                  => 'Adicionar',    ],

    'messages' => [    ],

    'breadcrumbs' => [
        'admin_panel' => 'Painel Admin',
    ],

    'tabs' => [
        'main_settings'      => 'Configurações Principais',        'localization'       => 'Localização',    ],

    'blocks' => [
        'main_settings'                    => 'Configurações Principais',    ],

    'popovers' => [        'cron_mode'                 => 'Isto usa CRON em vez de solicitações normais. Veja <a target="_blank" href="https://docs.flute-cms.com">aqui</a>.',        'reset_password'            => 'Habilitar recuperação de senha do usuário.',
        'only_social'               => 'Autenticação padrão por login/senha será desativado.',        'check_ip'                  => 'Vincule cada sessão a um endereço IP específico.',        'debug_ips'                 => 'A depuração funciona apenas com estes endereços IP. Separe por vírgulas.',    ],

    'examples' => [    ],

    'modals' => [    ],

    'databaseName'             => 'Nome',
    'host'                     => 'Servidor',
    'user'                     => 'Usuário',
    'database'                 => 'Banco de Dados',
    'prefix'                   => 'Prefixo',
    'actions'                  => 'Ações',
    'edit'                     => 'Editar',
    'delete'                   => 'Excluir',
    'confirm_delete_database'  => 'Tem certeza de que deseja excluir este banco de dados?',
    'add_database'             => 'Adicionar Banco de Dados',
];
