<?php

return [
    'title' => 'Navigering',
    'description' => 'Denna sida listar alla navigationsobjekt skapade i Flute',
    'table' => [
        'title' => 'Titel',
        'actions' => 'Åtgärder',
    ],
    'buttons' => [
        'create' => 'Skapa objekt',
        'edit' => 'Redigera',
        'delete' => 'Ta bort',
    ],
    'modal' => [
        'item' => [
            'create_title' => 'Skapa navigationsobjekt',
            'edit_title' => 'Redigera navigationsobjekt',
            'fields' => [
                'title' => [
                    'label' => 'Titel',
                    'placeholder' => 'Ange objekttitel',
                    'help' => 'Navigationsobjektets titel',
                ],
                'description' => [
                    'label' => 'Beskrivning',
                    'placeholder' => 'Ange objektbeskrivning (valfritt)',
                    'help' => 'Valfri beskrivning för navigationsobjektet',
                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'Ange URL (t.ex. /home)',
                    'help' => 'Länkadress. Lämna tom om objektet har underordnade.',
                ],
                'new_tab' => [
                    'label' => 'Öppna i ny flik',
                    'help' => 'Fungerar endast om URL är angiven',
                ],
                'icon' => [
                    'label' => 'Ikon',
                    'placeholder' => 'Ange ikon (t.ex. ph.regular.house)',
                ],
                'visibility_auth' => [
                    'label' => 'Synlighet',
                    'help' => 'Vem kan se detta navigationsobjekt',
                    'options' => [
                        'all' => 'Alla',
                        'guests' => 'Endast gäster',
                        'logged_in' => 'Endast inloggade',
                    ],
                ],
                'visibility' => [
                    'label' => 'Visningstyp',
                    'help' => 'Var detta objekt kommer att visas',
                    'options' => [
                        'all' => 'Alla',
                        'desktop' => 'Endast desktop',
                        'mobile' => 'Endast mobil',
                    ],
                ],
            ],
            'roles' => [
                'title' => 'Roller',
                'help' => 'Vilka roller kan se detta objekt. Om ingen är vald, synlig för alla användare',
            ],
        ],
    ],
    'confirms' => [
        'delete_item' => 'Är du säker på att du vill ta bort detta navigationsobjekt?',
    ],
    'messages' => [
        'invalid_sort_data' => 'Ogiltig sorteringsdata.',
        'item_created' => 'Navigationsobjekt skapat framgångsrikt.',
        'item_updated' => 'Navigationsobjekt uppdaterat framgångsrikt.',
        'item_deleted' => 'Navigationsobjekt borttaget framgångsrikt.',
        'item_not_found' => 'Navigationsobjekt hittades inte.',
    ],
];
