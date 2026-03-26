<?php

return [
    'title' => [
        'social' => 'Sociala nätverk',
        'description' => 'På denna sida kan du konfigurera sociala nätverk för autentisering',
        'edit' => 'Redigera socialt nätverk: :name',
        'create' => 'Lägg till socialt nätverk',
    ],
    'table' => [
        'social' => 'Socialt nätverk',
        'cooldown' => 'Cooldown',
        'registration' => 'Registrering',
        'status' => 'Status',
        'actions' => 'Åtgärder',
    ],
    'fields' => [
        'icon' => [
            'label' => 'Ikon',
            'placeholder' => 't.ex.: ph.regular.steam',
        ],
        'allow_register' => [
            'label' => 'Tillåt registrering',
            'help' => 'Kan registrera via detta sociala nätverk',
        ],
        'cooldown_time' => [
            'label' => 'Cooldown-tid',
            'help' => 'Exempel: 3600 (sekunder, motsvarar 1 timme)',
            'small' => 'Exempel: 3600 sekunder (1 timme)',
            'placeholder' => '3600 sekunder',
            'popover' => 'Tid mellan att ta bort en social länk och kunna lägga till den igen',
        ],
        'redirect_uri' => [
            'first' => 'Första URI',
            'second' => 'Andra URI',
        ],
        'driver' => [
            'label' => 'Autentiseringsdrivrutin',
            'placeholder' => 'Välj drivrutin',
        ],
        'client_id' => [
            'label' => 'Klient-ID',
        ],
        'client_secret' => [
            'label' => 'Klienthemlighet',
        ],
    ],
    'buttons' => [
        'add' => 'Lägg till',
        'save' => 'Spara',
        'edit' => 'Redigera',
        'delete' => 'Ta bort',
        'enable' => 'Aktivera',
        'disable' => 'Inaktivera',
    ],
    'status' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
    ],
    'confirms' => [
        'delete' => 'Är du säker på att du vill ta bort detta sociala nätverk?',
    ],
    'messages' => [
        'save_success' => 'Socialt nätverk sparat framgångsrikt.',
        'save_error' => 'Fel vid sparande: :message',
        'delete_success' => 'Socialt nätverk borttaget framgångsrikt.',
        'delete_error' => 'Fel vid borttagning: :message',
        'toggle_success' => 'Status för socialt nätverk ändrad framgångsrikt.',
        'toggle_error' => 'Fel vid ändring av status.',
        'not_found' => 'Socialt nätverk hittades inte.',
    ],
    'edit' => [
        'default' => 'Drivrutin :driver är otestad. Den kanske inte fungerar korrekt. Du måste konfigurera parametrar manuellt.',
        'steam_success' => 'Allt är bra, ingen installation krävs.',
        'steam_error' => 'Ingen STEAM API-nyckel inställd. Konfigurera den i <a href="/admin/main-settings" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">inställningar</a>.',
    ],
    'no_drivers' => 'Inga drivrutiner tillgängliga.',
];
