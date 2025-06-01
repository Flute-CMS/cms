<?php

return [
    'title' => [
        'social'      => 'Social Networks',
        'description' => 'On this page you can configure social networks for authentication',
        'edit'        => 'Edit Social Network: :name',
        'create'      => 'Add Social Network',
    ],
    'table' => [
        'social'       => 'Social Network',
        'cooldown'     => 'Cooldown',
        'registration' => 'Registration',
        'status'       => 'Status',
        'actions'      => 'Actions',
    ],
    'fields' => [
        'icon' => [
            'label'       => 'Icon',
            'placeholder' => 'e.g.: ph.regular.steam',
        ],
        'allow_register' => [
            'label' => 'Allow Registration',
            'help'  => 'Can register via this social network',
        ],
        'cooldown_time' => [
            'label'       => 'Cooldown Time',
            'help'        => 'Example: 3600 (seconds, equals 1 hour)',
            'small'       => 'Example: 3600 seconds (1 hour)',
            'placeholder' => '3600 seconds',
            'popover'     => 'Time between removing a social link and being able to add it again',
        ],
        'redirect_uri' => [
            'first'  => 'First URI',
            'second' => 'Second URI',
        ],
        'driver' => [
            'label'       => 'Auth Driver',
            'placeholder' => 'Select driver',
        ],
        'client_id' => [
            'label' => 'Client ID',
        ],
        'client_secret' => [
            'label' => 'Client Secret',
        ],
    ],
    'buttons' => [
        'add'     => 'Add',
        'save'    => 'Save',
        'edit'    => 'Edit',
        'delete'  => 'Delete',
        'enable'  => 'Enable',
        'disable' => 'Disable',
    ],
    'status' => [
        'active'   => 'Active',
        'inactive' => 'Inactive',
    ],
    'confirms' => [
        'delete' => 'Are you sure you want to delete this social network?',
    ],
    'messages' => [
        'save_success'    => 'Social network saved successfully.',
        'save_error'      => 'Error saving: :message',
        'delete_success'  => 'Social network deleted successfully.',
        'delete_error'    => 'Error deleting: :message',
        'toggle_success'  => 'Social network status changed successfully.',
        'toggle_error'    => 'Error changing status.',
        'not_found'       => 'Social network not found.',
    ],
    'edit' => [
        'default'         => 'Driver :driver is untested. It may not work correctly. You need to configure parameters manually.',
        'discord'         => 'For Discord setup see <a href="https://docs.flute-cms.com/social-auth/discord" target="_blank">documentation</a>.',
        'discord_token'   => 'Bot Token',
        'discord_token_help'=> 'Needed for <a class="accent" href="#">role sync with Discord</a>. Optional.',
        'steam_success'   => 'Everything is good, no setup required.',
        'steam_error'     => 'No STEAM API key set. Please configure it in <a href="/admin/main-settings" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">settings</a>.',
        'telegram'        => 'For Telegram setup see <a href="https://docs.flute-cms.com/social-auth/telegram" target="_blank">documentation</a>.',
        'telegram_token'  => 'Bot Token',
        'telegram_token_placeholder'=> '1234546',
        'telegram_bot_name'=> 'Bot Name',
        'telegram_bot_name_placeholder'=> 'e.g.: MyAwesomeBot',
    ],
    'no_drivers' => 'No drivers available.',
];
