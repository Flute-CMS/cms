<?php

return [
    'title' => [
        'social' => 'Sociální sítě',
        'description' => 'Na této stránce můžete konfigurovat sociální sítě pro autentizaci',
        'edit' => 'Upravit sociální síť: :name',
        'create' => 'Přidat sociální síť',
    ],
    'table' => [
        'social' => 'Sociální síť',
        'cooldown' => 'Cooldown',
        'registration' => 'Registrace',
        'status' => 'Stav',
        'actions' => 'Akce',
    ],
    'fields' => [
        'icon' => [
            'label' => 'Ikona',
            'placeholder' => 'např.: ph.regular.steam',
        ],
        'allow_register' => [
            'label' => 'Povolit registraci',
            'help' => 'Může se registrovat přes tuto sociální síť',
        ],
        'cooldown_time' => [
            'label' => 'Doba cooldownu',
            'help' => 'Příklad: 3600 (sekund, rovná se 1 hodině)',
            'small' => 'Příklad: 3600 sekund (1 hodina)',
            'placeholder' => '3600 sekund',
            'popover' => 'Čas mezi odebráním sociálního odkazu a možností jej znovu přidat',
        ],
        'redirect_uri' => [
            'first' => 'První URI',
            'second' => 'Druhé URI',
        ],
        'driver' => [
            'label' => 'Autentizační ovladač',
            'placeholder' => 'Vyberte ovladač',
        ],
        'client_id' => [
            'label' => 'Client ID',
        ],
        'client_secret' => [
            'label' => 'Client Secret',
        ],
    ],
    'buttons' => [
        'add' => 'Přidat',
        'save' => 'Uložit',
        'edit' => 'Upravit',
        'delete' => 'Smazat',
        'enable' => 'Povolit',
        'disable' => 'Zakázat',
    ],
    'status' => [
        'active' => 'Aktivní',
        'inactive' => 'Neaktivní',
    ],
    'confirms' => [
        'delete' => 'Opravdu chcete smazat tuto sociální síť?',
    ],
    'messages' => [
        'save_success' => 'Sociální síť byla úspěšně uložena.',
        'save_error' => 'Chyba při ukládání: :message',
        'delete_success' => 'Sociální síť byla úspěšně smazána.',
        'delete_error' => 'Chyba při mazání: :message',
        'toggle_success' => 'Stav sociální sítě byl úspěšně změněn.',
        'toggle_error' => 'Chyba při změně stavu.',
        'not_found' => 'Sociální síť nebyla nalezena.',
    ],
    'edit' => [
        'default' => 'Ovladač :driver není testován. Nemusí fungovat správně. Musíte nakonfigurovat parametry ručně.',
        'steam_success' => 'Vše je v pořádku, není nutné nastavení.',
        'steam_error' => 'Není nastaven STEAM API klíč. Nastavte jej v <a href="/admin/main-settings" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">nastavení</a>.',
    ],
    'no_drivers' => 'Nejsou k dispozici žádné ovladače.',
];
