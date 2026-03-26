<?php

return [
    'title' => 'Omdirigeringar',
    'description' => 'Hantera URL-omdirigeringar med villkor',

    'fields' => [
        'from_url' => [
            'label' => 'Från URL',
            'placeholder' => '/gammal-sida',
            'help' => 'URL-sökvägen att omdirigera från (t.ex. /gammal-sida)',
        ],
        'to_url' => [
            'label' => 'Till URL',
            'placeholder' => '/ny-sida',
            'help' => 'Mål-URL att omdirigera till',
        ],
        'conditions' => [
            'label' => 'Villkor',
            'help' => 'Valfria villkor som måste uppfyllas för att omdirigeringen ska aktiveras',
        ],
        'condition_type' => [
            'label' => 'Typ',
            'placeholder' => 'Välj villkorstyp',
        ],
        'condition_operator' => [
            'label' => 'Operator',
            'placeholder' => 'Välj operator',
        ],
        'condition_value' => [
            'label' => 'Värde',
            'placeholder' => 'Ange värde',
        ],
    ],

    'condition_types' => [
        'ip' => 'IP-adress',
        'cookie' => 'Cookie',
        'referer' => 'Hänvisare',
        'request_method' => 'HTTP-metod',
        'user_agent' => 'User Agent',
        'header' => 'HTTP-huvud',
        'lang' => 'Språk',
    ],

    'operators' => [
        'equals' => 'Lika med',
        'not_equals' => 'Inte lika med',
        'contains' => 'Innehåller',
        'not_contains' => 'Innehåller inte',
    ],

    'buttons' => [
        'add' => 'Lägg till omdirigering',
        'save' => 'Spara',
        'edit' => 'Redigera',
        'delete' => 'Ta bort',
        'actions' => 'Åtgärder',
        'add_condition_group' => 'Lägg till villkorsgrupp',
        'add_condition' => 'Lägg till villkor',
        'remove_condition' => 'Ta bort',
        'clear_cache' => 'Rensa cache',
    ],

    'messages' => [
        'save_success' => 'Omdirigeringen har sparats.',
        'update_success' => 'Omdirigeringen har uppdaterats.',
        'delete_success' => 'Omdirigeringen har tagits bort.',
        'not_found' => 'Omdirigeringen hittades inte.',
        'cache_cleared' => 'Omdirigerings-cachen har rensats.',
        'route_conflict' => 'Varning: URL:en ":url" står i konflikt med en befintlig rutt ":route". Omdirigeringen kanske inte fungerar eftersom rutten har prioritet.',
        'from_url_required' => 'Fältet "Från URL" är obligatoriskt.',
        'to_url_required' => 'Fältet "Till URL" är obligatoriskt.',
        'same_urls' => '"Från URL" och "Till URL" kan inte vara samma.',
    ],

    'empty' => [
        'title' => 'Inga omdirigeringar ännu',
        'sub' => 'Skapa din första omdirigering för att börja hantera URL-vidarebefordran',
    ],

    'confirms' => [
        'delete' => 'Är du säker på att du vill ta bort denna omdirigering? Åtgärden kan inte ångras.',
    ],

    'table' => [
        'from' => 'Från',
        'to' => 'Till',
        'conditions' => 'Villkor',
        'actions' => 'Åtgärder',
    ],

    'modal' => [
        'create_title' => 'Skapa omdirigering',
        'edit_title' => 'Redigera omdirigering',
        'conditions_title' => 'Omdirigeringsvillkor',
        'conditions_help' => 'Mellan villkorsgrupper används ELLER-logik, inom en grupp — OCH-logik.',
        'group_label' => 'Grupp :number',
    ],

    'settings' => [
        'title' => 'Inställningar',
        'cache_time' => [
            'label' => 'Cache-tid (sekunder)',
            'help' => 'Hur länge omdirigeringsregler cachelagras. Ange 0 för att inaktivera.',
            'placeholder' => '3600',
        ],
    ],

    'alert' => [
        'route_conflict_title' => 'Ruttkonflikt upptäckt',
    ],
];
