<?php

return [
    'title' => [
        'roles' => 'Role',
        'roles_description' => 'Správa uživatelských rolí. Nejvyšší role má nejvyšší prioritu.',
    ],
    'breadcrumbs' => [
        'roles' => 'Role',
    ],
    'buttons' => [
        'create' => 'Vytvořit roli',
        'edit' => 'Upravit',
        'delete' => 'Smazat',
        'save' => 'Uložit',
        'update' => 'Aktualizovat',
    ],
    'table' => [
        'role_name' => 'Název role',
        'actions' => 'Akce',
    ],
    'modal' => [
        'create' => [
            'title' => 'Vytvořit roli',
            'submit' => 'Vytvořit',
        ],
        'edit' => [
            'title' => 'Upravit roli',
            'submit' => 'Aktualizovat',
        ],
        'delete' => [
            'title' => 'Smazat roli',
            'confirm' => 'Opravdu chcete smazat tuto roli?',
        ],
    ],
    'fields' => [
        'name' => [
            'label' => 'Název role',
            'placeholder' => 'Zadejte název role',
            'help' => 'Unikátní název pro roli',
        ],
        'color' => [
            'label' => 'Barva',
            'help' => 'Barva přidružená k roli',
        ],
        'permissions' => [
            'label' => 'Oprávnění',
            'help' => 'Vyberte oprávnění pro tuto roli',
        ],
        'icon' => [
            'label' => 'Ikona',
            'placeholder' => 'ph.regular... nebo <svg...',
            'help' => 'Ikona přidružená k roli',
        ],
    ],
    'messages' => [
        'created' => 'Role byla úspěšně vytvořena.',
        'updated' => 'Role byla úspěšně aktualizována.',
        'deleted' => 'Role byla úspěšně smazána.',
        'not_found' => 'Role nebyla nalezena nebo nemáte oprávnění ji upravovat.',
        'invalid_sort' => 'Neplatná data řazení.',
        'no_permissions' => 'Vyberte prosím alespoň jedno oprávnění.',
    ],
];
