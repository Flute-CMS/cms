<?php

return [
    'title' => 'Zápatí',
    'description' => 'Správa položek zápatí a sociálních odkazů',
    'tabs' => [
        'main_elements' => 'Hlavní položky',
        'social' => 'Sociální sítě',
    ],
    'table' => [
        'title' => 'Název',
        'icon' => 'Ikona',
        'url' => 'URL',
        'actions' => 'Akce',
    ],
    'sections' => [
        'main_links' => [
            'title' => 'Hlavní odkazy',
            'description' => 'Tato stránka zobrazuje všechny vytvořené položky zápatí ve Flute',
        ],
        'social_links' => [
            'title' => 'Sociální odkazy v zápatí',
            'description' => 'Tato stránka zobrazuje všechny sociální sítě zobrazené v zápatí webu',
        ],
    ],
    'buttons' => [
        'create' => 'Vytvořit',
        'edit' => 'Upravit',
        'delete' => 'Smazat',
    ],
    'modal' => [
        'footer_item' => [
            'create_title' => 'Vytvořit položku zápatí',
            'edit_title' => 'Upravit položku zápatí',
            'fields' => [
                'title' => [
                    'label' => 'Název',
                    'placeholder' => 'Zadejte název položky',
                    'help' => 'Název položky zápatí',
                ],
                'icon' => [
                    'label' => 'Ikona',
                    'placeholder' => 'Zadejte ikonu (např. ph.regular.home)',
                    'help' => 'Identifikátor ikony (nepovinné)',
                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'Zadejte URL (např. /contact)',
                    'help' => 'Adresa odkazu. Nechte prázdné, pokud má položka podřízené položky.',
                ],
                'new_tab' => [
                    'label' => 'Otevřít v nové záložce',
                    'help' => 'Funguje pouze pokud je zadána URL',
                ],
            ],
        ],
        'social' => [
            'create_title' => 'Vytvořit sociální síť',
            'edit_title' => 'Upravit sociální síť',
            'fields' => [
                'name' => [
                    'label' => 'Název',
                    'placeholder' => 'Zadejte název sociální sítě',
                    'help' => 'Název sociální sítě (např. Discord)',
                ],
                'icon' => [
                    'label' => 'Ikona',
                    'placeholder' => 'Zadejte ikonu (např. ph.regular.discord-logo)',
                    'help' => 'Identifikátor ikony, např. "ph.bold.discord-logo-bold"',
                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'Zadejte URL (např. https://discord.gg/yourpage)',
                    'help' => 'Odkaz na vaši stránku na sociální síti',
                ],
            ],
        ],
    ],
    'confirms' => [
        'delete_item' => 'Opravdu chcete smazat tuto položku zápatí?',
        'delete_social' => 'Opravdu chcete smazat tuto sociální síť?',
    ],
    'messages' => [
        'invalid_sort_data' => 'Neplatná data řazení.',
        'item_created' => 'Položka zápatí byla úspěšně vytvořena.',
        'item_updated' => 'Položka zápatí byla úspěšně aktualizována.',
        'item_deleted' => 'Položka zápatí byla úspěšně smazána.',
        'item_not_found' => 'Položka zápatí nebyla nalezena.',
        'social_created' => 'Sociální síť byla úspěšně vytvořena.',
        'social_updated' => 'Sociální síť byla úspěšně aktualizována.',
        'social_deleted' => 'Sociální síť byla úspěšně smazána.',
        'social_not_found' => 'Sociální síť nebyla nalezena.',
    ],
];
