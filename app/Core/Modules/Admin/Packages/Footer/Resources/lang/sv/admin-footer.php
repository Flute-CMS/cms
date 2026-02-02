<?php

return [
    'title' => 'Sidfot',
    'description' => 'Hantera sidfotsobjekt och sociala länkar',
    'tabs' => [
        'main_elements' => 'Huvudobjekt',
        'social' => 'Sociala nätverk',
    ],
    'table' => [
        'title' => 'Titel',
        'icon' => 'Ikon',
        'url' => 'URL',
        'actions' => 'Åtgärder',
    ],
    'sections' => [
        'main_links' => [
            'title' => 'Huvudlänkar',
            'description' => 'Denna sida listar alla skapade sidfotsobjekt i Flute',
        ],
        'social_links' => [
            'title' => 'Sociala länkar i sidfot',
            'description' => 'Denna sida listar alla sociala nätverk som visas i webbplatsens sidfot',
        ],
    ],
    'buttons' => [
        'create' => 'Skapa',
        'edit' => 'Redigera',
        'delete' => 'Ta bort',
    ],
    'modal' => [
        'footer_item' => [
            'create_title' => 'Skapa sidfotsobjekt',
            'edit_title' => 'Redigera sidfotsobjekt',
            'fields' => [
                'title' => [
                    'label' => 'Titel',
                    'placeholder' => 'Ange objekttitel',
                    'help' => 'Sidfotsobjektets titel',
                ],
                'icon' => [
                    'label' => 'Ikon',
                    'placeholder' => 'Ange ikon (t.ex. ph.regular.home)',
                    'help' => 'Ikonidentifierare (valfritt)',
                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'Ange URL (t.ex. /contact)',
                    'help' => 'Länkadress. Lämna tom om objektet har underordnade.',
                ],
                'new_tab' => [
                    'label' => 'Öppna i ny flik',
                    'help' => 'Fungerar endast om URL är angiven',
                ],
            ],
        ],
        'social' => [
            'create_title' => 'Skapa socialt nätverk',
            'edit_title' => 'Redigera socialt nätverk',
            'fields' => [
                'name' => [
                    'label' => 'Namn',
                    'placeholder' => 'Ange namn på socialt nätverk',
                    'help' => 'Socialt nätverksnamn (t.ex. Discord)',
                ],
                'icon' => [
                    'label' => 'Ikon',
                    'placeholder' => 'Ange ikon (t.ex. ph.regular.discord-logo)',
                    'help' => 'Ikonidentifierare, t.ex. "ph.bold.discord-logo-bold"',
                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'Ange URL (t.ex. https://discord.gg/yourpage)',
                    'help' => 'Länk till din sida på det sociala nätverket',
                ],
            ],
        ],
    ],
    'confirms' => [
        'delete_item' => 'Är du säker på att du vill ta bort detta sidfotsobjekt?',
        'delete_social' => 'Är du säker på att du vill ta bort detta sociala nätverk?',
    ],
    'messages' => [
        'invalid_sort_data' => 'Ogiltig sorteringsdata.',
        'item_created' => 'Sidfotsobjekt skapat framgångsrikt.',
        'item_updated' => 'Sidfotsobjekt uppdaterat framgångsrikt.',
        'item_deleted' => 'Sidfotsobjekt borttaget framgångsrikt.',
        'item_not_found' => 'Sidfotsobjekt hittades inte.',
        'social_created' => 'Socialt nätverk skapat framgångsrikt.',
        'social_updated' => 'Socialt nätverk uppdaterat framgångsrikt.',
        'social_deleted' => 'Socialt nätverk borttaget framgångsrikt.',
        'social_not_found' => 'Socialt nätverk hittades inte.',
    ],
];
