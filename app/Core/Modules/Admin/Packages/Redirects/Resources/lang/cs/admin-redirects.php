<?php

return [
    'title' => 'Přesměrování',
    'description' => 'Správa URL přesměrování s podmínkami',

    'fields' => [
        'from_url' => [
            'label' => 'Z URL',
            'placeholder' => '/stara-stranka',
            'help' => 'URL cesta pro přesměrování (např. /stara-stranka)',
        ],
        'to_url' => [
            'label' => 'Na URL',
            'placeholder' => '/nova-stranka',
            'help' => 'Cílová URL pro přesměrování',
        ],
        'conditions' => [
            'label' => 'Podmínky',
            'help' => 'Volitelné podmínky, které musí být splněny pro aktivaci přesměrování',
        ],
        'condition_type' => [
            'label' => 'Typ',
            'placeholder' => 'Vyberte typ podmínky',
        ],
        'condition_operator' => [
            'label' => 'Operátor',
            'placeholder' => 'Vyberte operátor',
        ],
        'condition_value' => [
            'label' => 'Hodnota',
            'placeholder' => 'Zadejte hodnotu',
        ],
    ],

    'condition_types' => [
        'ip' => 'IP adresa',
        'cookie' => 'Cookie',
        'referer' => 'Referrer',
        'request_method' => 'HTTP metoda',
        'user_agent' => 'User Agent',
        'header' => 'HTTP hlavička',
        'lang' => 'Jazyk',
    ],

    'operators' => [
        'equals' => 'Rovná se',
        'not_equals' => 'Nerovná se',
        'contains' => 'Obsahuje',
        'not_contains' => 'Neobsahuje',
    ],

    'buttons' => [
        'add' => 'Přidat přesměrování',
        'save' => 'Uložit',
        'edit' => 'Upravit',
        'delete' => 'Smazat',
        'actions' => 'Akce',
        'add_condition_group' => 'Přidat skupinu podmínek',
        'add_condition' => 'Přidat podmínku',
        'remove_condition' => 'Odstranit',
        'clear_cache' => 'Vymazat cache',
    ],

    'messages' => [
        'save_success' => 'Přesměrování bylo úspěšně uloženo.',
        'update_success' => 'Přesměrování bylo úspěšně aktualizováno.',
        'delete_success' => 'Přesměrování bylo úspěšně smazáno.',
        'not_found' => 'Přesměrování nebylo nalezeno.',
        'cache_cleared' => 'Cache přesměrování byla úspěšně vymazána.',
        'route_conflict' => 'Upozornění: URL ":url" je v konfliktu s existující cestou ":route". Přesměrování nemusí fungovat, protože cesta má přednost.',
        'from_url_required' => 'Pole «Z URL» je povinné.',
        'to_url_required' => 'Pole «Na URL» je povinné.',
        'same_urls' => 'URL «Z» a «Na» nemohou být stejné.',
    ],

    'empty' => [
        'title' => 'Zatím žádná přesměrování',
        'sub' => 'Vytvořte první přesměrování pro správu přeposílání URL',
    ],

    'confirms' => [
        'delete' => 'Opravdu chcete smazat toto přesměrování? Tuto akci nelze vrátit zpět.',
    ],

    'table' => [
        'from' => 'Z',
        'to' => 'Na',
        'conditions' => 'Podmínky',
        'actions' => 'Akce',
    ],

    'modal' => [
        'create_title' => 'Vytvořit přesměrování',
        'edit_title' => 'Upravit přesměrování',
        'conditions_title' => 'Podmínky přesměrování',
        'conditions_help' => 'Mezi skupinami podmínek se používá logika NEBO, uvnitř skupiny — A.',
        'group_label' => 'Skupina :number',
    ],

    'settings' => [
        'title' => 'Nastavení',
        'cache_time' => [
            'label' => 'Doba cache (sekundy)',
            'help' => 'Jak dlouho jsou pravidla přesměrování ukládána do cache. Nastavte 0 pro deaktivaci.',
            'placeholder' => '3600',
        ],
    ],

    'alert' => [
        'route_conflict_title' => 'Zjištěn konflikt cest',
    ],
];
