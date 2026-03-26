<?php

return [
    'title' => [
        'themes' => 'Motivy',
        'description' => 'Na této stránce můžete spravovat motivy a jejich nastavení',
        'edit' => 'Upravit motiv: :name',
        'create' => 'Přidat motiv',
    ],
    'table' => [
        'name' => 'Název',
        'version' => 'Verze',
        'status' => 'Stav',
        'actions' => 'Akce',
    ],
    'fields' => [
        'name' => [
            'label' => 'Název',
            'placeholder' => 'Zadejte název motivu',
        ],
        'version' => [
            'label' => 'Verze',
            'placeholder' => 'Zadejte verzi motivu',
        ],
        'enabled' => [
            'label' => 'Povoleno',
            'help' => 'Povolit nebo zakázat tento motiv',
        ],
        'description' => [
            'label' => 'Popis',
            'placeholder' => 'Zadejte popis motivu',
        ],
        'author' => [
            'label' => 'Autor',
            'placeholder' => 'Zadejte autora motivu',
        ],
    ],
    'buttons' => [
        'save' => 'Uložit',
        'edit' => 'Upravit',
        'delete' => 'Smazat',
        'enable' => 'Povolit',
        'disable' => 'Zakázat',
        'refresh' => 'Obnovit seznam motivů',
        'details' => 'Podrobnosti',
        'install' => 'Instalovat',
    ],
    'status' => [
        'active' => 'Aktivní',
        'inactive' => 'Neaktivní',
        'not_installed' => 'Nenainstalováno',
    ],
    'confirms' => [
        'delete' => 'Opravdu chcete tento motiv smazat?',
        'install' => 'Opravdu chcete tento motiv instalovat?',
    ],
    'messages' => [
        'save_success' => 'Motiv byl úspěšně uložen.',
        'save_error' => 'Chyba při ukládání motivu: :message',
        'delete_success' => 'Motiv byl úspěšně smazán.',
        'delete_error' => 'Chyba při mazání motivu: :message',
        'toggle_success' => 'Stav motivu byl úspěšně změněn.',
        'toggle_error' => 'Chyba při změně stavu motivu.',
        'not_found' => 'Motiv nebyl nalezen.',
        'refresh_success' => 'Seznam motivů byl úspěšně obnoven.',
        'install_success' => 'Motiv byl úspěšně nainstalován.',
        'install_error' => 'Chyba při instalaci motivu: :message',
        'enable_success' => 'Motiv byl úspěšně povolen.',
        'enable_error' => 'Chyba při povolování motivu: :message',
        'disable_success' => 'Motiv byl úspěšně zakázán.',
        'disable_error' => 'Chyba při zakazování motivu: :message',
    ],
];
