<?php

return [
    'title' => [
        'list' => 'API klíče',
        'description' => 'Spravovat API klíče pro externí přístup',
        'create' => 'Vytvořit API klíč',
        'edit' => 'Upravit API klíč',
    ],
    'fields' => [
        'key' => [
            'label' => 'API klíč',
            'placeholder' => 'Zadejte API klíč',
            'help' => 'Tento klíč bude použit pro autentizaci API',
        ],
        'name' => [
            'label' => 'Název',
            'placeholder' => 'Zadejte název klíče',
            'help' => 'Tento název můžete použít k identifikaci klíče',
        ],
        'permissions' => [
            'label' => 'Oprávnění',
        ],
        'created_at' => 'Vytvořeno',
        'last_used_at' => 'Naposledy použito',
        'never' => 'Nikdy',
    ],
    'buttons' => [
        'actions' => 'Akce',
        'add' => 'Přidat klíč',
        'save' => 'Uložit',
        'edit' => 'Upravit',
        'delete' => 'Smazat',
    ],
    'confirms' => [
        'delete_key' => 'Opravdu chcete smazat tento API klíč?',
    ],
    'messages' => [
        'save_success' => 'API klíč byl úspěšně uložen.',
        'key_not_found' => 'API klíč nebyl nalezen.',
        'no_permissions' => 'Vyberte prosím alespoň jedno oprávnění.',
        'update_success' => 'API klíč byl úspěšně aktualizován.',
        'update_error' => 'Chyba při aktualizaci API klíče: :message',
        'delete_success' => 'API klíč byl úspěšně smazán.',
        'delete_error' => 'Chyba při mazání API klíče: :message',
    ],

    'info_alert' => [
        'title' => 'Je vyžadován modul API',
        'description' => 'API klíče umožňují autentizaci požadavků, ale pro fungování API je nutné nainstalovat modul API z marketplace.',
        'install_module' => 'Nainstalovat modul',
        'documentation' => 'Dokumentace',
    ],
];
