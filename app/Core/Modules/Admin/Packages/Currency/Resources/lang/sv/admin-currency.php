<?php

return [
    'title' => [
        'list' => 'Valutor',
        'edit' => 'Redigera valuta',
        'create' => 'Skapa valuta',
        'description' => 'Den här sidan visar alla systemvalutor',
        'main_info' => 'Huvudinformation',
        'actions' => 'Åtgärder',
        'actions_description' => 'Åtgärder för valutan',
    ],

    'fields' => [
        'name' => [
            'label' => 'Namn',
            'placeholder' => 'Ange valutanamn',
        ],
        'code' => [
            'label' => 'Kod',
            'placeholder' => 'Ange valutakod',
            'help' => 'Unik valutakod (t.ex.: USD, EUR, SEK)',
        ],
        'minimum_value' => [
            'label' => 'Minimibelopp',
            'placeholder' => 'Ange minimibelopp',
            'help' => 'Minsta påfyllningsbelopp för denna valuta',
        ],
        'rate' => [
            'label' => 'Kurs',
            'placeholder' => 'Ange valutakurs',
            'help' => 'Kurs i förhållande till basvalutan',
        ],
        'preset_amounts' => [
            'label' => 'Snabbbelopp',
            'placeholder' => '100, 500, 1000, 5000',
            'help' => 'Förinställda belopp för snabbval på påfyllningssidan (kommaseparerade)',
        ],
        'enabled' => [
            'label' => 'Aktiverad',
            'help' => 'En aktiverad valuta är tillgänglig för användning i systemet',
        ],
        'created_at' => 'Skapad',
        'updated_at' => 'Uppdaterad',
    ],

    'status' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'default' => 'Standard',
    ],

    'buttons' => [
        'add' => 'Lägg till valuta',
        'save' => 'Spara',
        'cancel' => 'Avbryt',
        'delete' => 'Ta bort',
        'edit' => 'Redigera',
        'actions' => 'Åtgärder',
        'update_rates' => 'Uppdatera kurser',
    ],

    'messages' => [
        'currency_not_found' => 'Valuta hittades inte.',
        'save_success' => 'Valuta sparad framgångsrikt.',
        'delete_success' => 'Valuta borttagen framgångsrikt.',
        'update_rates_success' => 'Valutakurser uppdaterade framgångsrikt.',
        'default_currency_delete' => 'Kan inte ta bort standardvalutan.',
        'no_permission.manage' => 'Du har inte behörighet att hantera valutor.',
        'no_permission.delete' => 'Du har inte behörighet att ta bort valutor.',
    ],

    'confirms' => [
        'delete_currency' => 'Är du säker på att du vill ta bort denna valuta? Denna åtgärd kan inte ångras.',
        'set_default' => 'Är du säker på att du vill ställa in denna valuta som standard? Alla kurser kommer att räknas om.',
    ],
];
