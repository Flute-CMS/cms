<?php

return [
    'title' => [
        'themes' => 'Teman',
        'description' => 'På denna sida kan du hantera teman och deras inställningar',
        'edit' => 'Redigera tema: :name',
        'create' => 'Lägg till tema',
    ],
    'table' => [
        'name' => 'Namn',
        'version' => 'Version',
        'status' => 'Status',
        'actions' => 'Åtgärder',
    ],
    'fields' => [
        'name' => [
            'label' => 'Namn',
            'placeholder' => 'Ange temanamn',
        ],
        'version' => [
            'label' => 'Version',
            'placeholder' => 'Ange temaversion',
        ],
        'enabled' => [
            'label' => 'Aktiverad',
            'help' => 'Aktivera eller inaktivera detta tema',
        ],
        'description' => [
            'label' => 'Beskrivning',
            'placeholder' => 'Ange temabeskrivning',
        ],
        'author' => [
            'label' => 'Författare',
            'placeholder' => 'Ange temaförfattare',
        ],
    ],
    'buttons' => [
        'save' => 'Spara',
        'edit' => 'Redigera',
        'delete' => 'Ta bort',
        'enable' => 'Aktivera',
        'disable' => 'Inaktivera',
        'refresh' => 'Uppdatera temalista',
        'details' => 'Detaljer',
        'install' => 'Installera',
    ],
    'status' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'not_installed' => 'Ej installerad',
    ],
    'confirms' => [
        'delete' => 'Är du säker på att du vill ta bort detta tema?',
        'install' => 'Är du säker på att du vill installera detta tema?',
    ],
    'messages' => [
        'save_success' => 'Tema sparat framgångsrikt.',
        'save_error' => 'Fel vid sparande av tema: :message',
        'delete_success' => 'Tema borttaget framgångsrikt.',
        'delete_error' => 'Fel vid borttagning av tema: :message',
        'toggle_success' => 'Temastatus ändrad framgångsrikt.',
        'toggle_error' => 'Fel vid ändring av temastatus.',
        'not_found' => 'Tema hittades inte.',
        'refresh_success' => 'Temalistan uppdaterad framgångsrikt.',
        'install_success' => 'Tema installerat framgångsrikt.',
        'install_error' => 'Fel vid installation av tema: :message',
        'enable_success' => 'Tema aktiverat framgångsrikt.',
        'enable_error' => 'Fel vid aktivering av tema: :message',
        'disable_success' => 'Tema inaktiverat framgångsrikt.',
        'disable_error' => 'Fel vid inaktivering av tema: :message',
    ],
];
