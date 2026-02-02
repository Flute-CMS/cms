<?php

return [
    'title' => 'Navigace',
    'description' => 'Tato stránka zobrazuje všechny vytvořené položky navigace ve Flute',
    'table' => [
        'title' => 'Název',
        'actions' => 'Akce',
    ],
    'buttons' => [
        'create' => 'Vytvořit položku',
        'edit' => 'Upravit',
        'delete' => 'Smazat',
    ],
    'modal' => [
        'item' => [
            'create_title' => 'Vytvořit položku navigace',
            'edit_title' => 'Upravit položku navigace',
            'fields' => [
                'title' => [
                    'label' => 'Název',
                    'placeholder' => 'Zadejte název položky',
                    'help' => 'Název položky navigace',
                ],
                'description' => [
                    'label' => 'Popis',
                    'placeholder' => 'Zadejte popis položky (nepovinné)',
                    'help' => 'Volitelný popis pro položku navigace',
                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'Zadejte URL (např. /home)',
                    'help' => 'Adresa odkazu. Nechte prázdné, pokud položka obsahuje podřízené položky.',
                ],
                'new_tab' => [
                    'label' => 'Otevřít v nové záložce',
                    'help' => 'Funguje pouze pokud je zadána URL',
                ],
                'icon' => [
                    'label' => 'Ikona',
                    'placeholder' => 'Zadejte ikonu (např. ph.regular.house)',
                ],
                'visibility_auth' => [
                    'label' => 'Viditelnost',
                    'help' => 'Kdo může vidět tuto položku navigace',
                    'options' => [
                        'all' => 'Všichni',
                        'guests' => 'Pouze hosté',
                        'logged_in' => 'Pouze přihlášení',
                    ],
                ],
                'visibility' => [
                    'label' => 'Typ zobrazení',
                    'help' => 'Kde bude položka zobrazena',
                    'options' => [
                        'all' => 'Všude',
                        'desktop' => 'Pouze desktop',
                        'mobile' => 'Pouze mobil',
                    ],
                ],
            ],
            'roles' => [
                'title' => 'Role',
                'help' => 'Které role mohou vidět tuto položku. Pokud není vybráno nic, je viditelná všem uživatelům',
            ],
        ],
    ],
    'confirms' => [
        'delete_item' => 'Opravdu chcete smazat tuto položku navigace?',
    ],
    'messages' => [
        'invalid_sort_data' => 'Neplatná data řazení.',
        'item_created' => 'Položka navigace byla úspěšně vytvořena.',
        'item_updated' => 'Položka navigace byla úspěšně aktualizována.',
        'item_deleted' => 'Položka navigace byla úspěšně smazána.',
        'item_not_found' => 'Položka navigace nebyla nalezena.',
    ],
];
