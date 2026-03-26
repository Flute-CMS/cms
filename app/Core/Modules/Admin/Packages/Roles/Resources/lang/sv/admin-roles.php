<?php

return [
    'title' => [
        'roles' => 'Roller',
        'roles_description' => 'Hantering av användarroller. Den högsta rollen har högst prioritet.',
    ],
    'breadcrumbs' => [
        'roles' => 'Roller',
    ],
    'buttons' => [
        'create' => 'Skapa roll',
        'edit' => 'Redigera',
        'delete' => 'Ta bort',
        'save' => 'Spara',
        'update' => 'Uppdatera',
    ],
    'table' => [
        'role_name' => 'Rollnamn',
        'actions' => 'Åtgärder',
    ],
    'modal' => [
        'create' => [
            'title' => 'Skapa roll',
            'submit' => 'Skapa',
        ],
        'edit' => [
            'title' => 'Redigera roll',
            'submit' => 'Uppdatera',
        ],
        'delete' => [
            'title' => 'Ta bort roll',
            'confirm' => 'Är du säker på att du vill ta bort denna roll?',
        ],
    ],
    'fields' => [
        'name' => [
            'label' => 'Rollnamn',
            'placeholder' => 'Ange rollnamn',
            'help' => 'Ett unikt namn för rollen',
        ],
        'color' => [
            'label' => 'Färg',
            'help' => 'Färg associerad med rollen',
        ],
        'permissions' => [
            'label' => 'Behörigheter',
            'help' => 'Välj behörigheter för denna roll',
        ],
        'icon' => [
            'label' => 'Ikon',
            'placeholder' => 'ph.regular... eller <svg...',
            'help' => 'Ikon associerad med rollen',
        ],
    ],
    'messages' => [
        'created' => 'Roll skapad framgångsrikt.',
        'updated' => 'Roll uppdaterad framgångsrikt.',
        'deleted' => 'Roll borttagen framgångsrikt.',
        'not_found' => 'Roll hittades inte eller du har inte behörighet att redigera den.',
        'invalid_sort' => 'Ogiltig sorteringsdata.',
        'no_permissions' => 'Välj minst en behörighet.',
    ],
];
