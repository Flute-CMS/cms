<?php

return [
    'title' => [
        'list' => 'Měny',
        'edit' => 'Upravit měnu',
        'create' => 'Vytvořit měnu',
        'description' => 'Tato stránka zobrazuje všechny systémové měny',
        'main_info' => 'Hlavní informace',
        'actions' => 'Akce',
        'actions_description' => 'Akce s měnou',
    ],

    'fields' => [
        'name' => [
            'label' => 'Název',
            'placeholder' => 'Zadejte název měny',
        ],
        'code' => [
            'label' => 'Kód',
            'placeholder' => 'Zadejte kód měny',
            'help' => 'Unikátní kód měny (např.: USD, EUR, CZK)',
        ],
        'minimum_value' => [
            'label' => 'Minimální částka',
            'placeholder' => 'Zadejte minimální částku',
            'help' => 'Minimální částka dobití pro tuto měnu',
        ],
        'rate' => [
            'label' => 'Kurz',
            'placeholder' => 'Zadejte kurz měny',
            'help' => 'Kurz vzhledem k základní měně',
        ],
        'enabled' => [
            'label' => 'Povoleno',
            'help' => 'Povolená měna je k dispozici pro použití v systému',
        ],
        'created_at' => 'Vytvořeno',
        'updated_at' => 'Aktualizováno',
    ],

    'status' => [
        'active' => 'Aktivní',
        'inactive' => 'Neaktivní',
        'default' => 'Výchozí',
    ],

    'buttons' => [
        'add' => 'Přidat měnu',
        'save' => 'Uložit',
        'cancel' => 'Zrušit',
        'delete' => 'Smazat',
        'edit' => 'Upravit',
        'actions' => 'Akce',
        'update_rates' => 'Aktualizovat kurzy',
    ],

    'messages' => [
        'currency_not_found' => 'Měna nenalezena.',
        'save_success' => 'Měna úspěšně uložena.',
        'delete_success' => 'Měna úspěšně smazána.',
        'update_rates_success' => 'Kurzy měn úspěšně aktualizovány.',
        'default_currency_delete' => 'Nelze smazat výchozí měnu.',
        'no_permission.manage' => 'Nemáte oprávnění spravovat měny.',
        'no_permission.delete' => 'Nemáte oprávnění mazat měny.',
    ],

    'confirms' => [
        'delete_currency' => 'Opravdu chcete tuto měnu smazat? Tuto akci nelze vrátit zpět.',
        'set_default' => 'Opravdu chcete nastavit tuto měnu jako výchozí? Všechny kurzy budou přepočítány.',
    ],
];
