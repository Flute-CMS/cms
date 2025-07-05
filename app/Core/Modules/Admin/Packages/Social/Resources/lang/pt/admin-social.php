<?php

return [
    'title' => [        'edit'        => 'Editar Rede Social: :name',    ],
    'table' => [        'actions'      => 'Ações',
    ],
    'fields' => [
        'icon' => [        ],
        'allow_register' => [        ],
        'cooldown_time' => [        ],
        'redirect_uri' => [        ],
        'driver' => [        ],
        'client_id' => [
            'label' => 'Client ID',
        ],
        'client_secret' => [
            'label' => 'Client Secret',
        ],
    ],
    'buttons' => [    ],
    'status' => [        'inactive' => 'Inativo',
    ],
    'confirms' => [    ],
    'messages' => [    ],
    'edit' => [        'discord_token'   => 'Bot Token',        'steam_error'     => 'Nenhuma chave API STEAM definida. Configure-a em  <a href="/admin/main-settings" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">configurações</a>.',        'telegram_token'  => 'Bot Token',
        'telegram_token_placeholder'=> '1234546',
        'telegram_bot_name'=> 'Nome do Bot',
        'telegram_bot_name_placeholder'=> 'e.x.: MyAwesomeBot',
    ],];
