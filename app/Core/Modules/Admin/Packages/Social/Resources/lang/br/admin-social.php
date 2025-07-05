<?php

return [
    'title' => [
        'social'      => 'Redes Sociais',
        'description' => 'Nesta página você pode configurar redes sociais para autenticação',
        'edit'        => 'Editar Rede Social: :name',
        'create'      => 'Adicionar Rede Social',
    ],
    'table' => [
        'social'       => 'Rede Social',
        'cooldown'     => 'Cooldown',
        'registration' => 'Registro',
        'status'       => 'Status',
        'actions'      => 'Ações',
    ],
    'fields' => [
        'icon' => [
            'label'       => 'Ícone',
            'placeholder' => 'ex.: ph.regular.steam',
        ],
        'allow_register' => [
            'label' => 'Permitir Registro',
            'help'  => 'Pode se registrar através desta rede social',
        ],
        'cooldown_time' => [
            'label'       => 'Tempo de Cooldown',
            'help'        => 'Exemplo: 3600 (segundos, igual a 1 hora)',
            'small'       => 'Exemplo: 3600 segundos (1 hora)',
            'placeholder' => '3600 segundos',
            'popover'     => 'Tempo entre remover um link social e adicioná-lo novamente',
        ],
        'redirect_uri' => [
            'first'  => 'Primeira URL',
            'second' => 'Segunda URL',
        ],
        'driver' => [
            'label'       => 'Driver de Autenticação',
            'placeholder' => 'Selecionar driver',
        ],
        'client_id' => [
            'label' => 'Client ID',
        ],
        'client_secret' => [
            'label' => 'Client Secret',
        ],
    ],
    'buttons' => [
        'add'     => 'Adicionar',
        'save'    => 'Salvar',
        'edit'    => 'Editar',
        'delete'  => 'Deletar',
        'enable'  => 'Habilitado',
        'disable' => 'Desabilitado',
    ],
    'status' => [
        'active'   => 'Ativo',
        'inactive' => 'Inativo',
    ],
    'confirms' => [
        'delete' => 'Tem certeza de que deseja excluir esta rede social?',
    ],
    'messages' => [
        'save_success'    => 'Rede social salva com sucesso.',
        'save_error'      => 'Erro ao salvar: :message',
        'delete_success'  => 'Rede social excluída com sucesso.',
        'delete_error'    => 'Erro ao excluir: :message',
        'toggle_success'  => 'Status da rede social alterado com sucesso.',
        'toggle_error'    => 'Erro ao alterar status.',
        'not_found'       => 'Rede social não encontrada.',
    ],
    'edit' => [
        'default'         => 'Driver :driver não funcionou. Talvez ele não funcione corretamente. Você precisa configurar os parâmetros manualmente.',
        'discord'         => 'Para configurar o Discord, consulte <a href="https://docs.flute-cms.com/social-auth/discord" target="_blank">documentação</a>.',
        'discord_token'   => 'Bot Token',
        'discord_token_help'=> 'Necessário para <a class="accent" href="#">sincronização de cargos com o Discord</a>. Opcional.',
        'steam_success'   => 'Tudo está bom, nenhuma configuração é necessária.',
        'steam_error'     => 'Nenhuma chave API STEAM definida. Configure-a em  <a href="/admin/main-settings" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">configurações</a>.',
        'telegram'        => 'Para configuração do Telegram, consulte <a href="https://docs.flute-cms.com/social-auth/telegram" target="_blank">documentação</a>.',
        'telegram_token'  => 'Bot Token',
        'telegram_token_placeholder'=> '1234546',
        'telegram_bot_name'=> 'Nome do Bot',
        'telegram_bot_name_placeholder'=> 'e.x.: MyAwesomeBot',
    ],
    'no_drivers' => 'Nenhum driver disponível.',
];
