<?php

return [
    'title' => 'Пренасочвания',
    'description' => 'Управление на URL пренасочвания с условия',

    'fields' => [
        'from_url' => [
            'label' => 'От URL',
            'placeholder' => '/old-page',
            'help' => 'URL пътят за пренасочване (напр. /old-page)',
        ],
        'to_url' => [
            'label' => 'Към URL',
            'placeholder' => '/new-page',
            'help' => 'Целевият URL за пренасочване',
        ],
        'conditions' => [
            'label' => 'Условия',
            'help' => 'Незадължителни условия за задействане на пренасочването',
        ],
        'condition_type' => [
            'label' => 'Тип',
            'placeholder' => 'Изберете тип условие',
        ],
        'condition_operator' => [
            'label' => 'Оператор',
            'placeholder' => 'Изберете оператор',
        ],
        'condition_value' => [
            'label' => 'Стойност',
            'placeholder' => 'Въведете стойност',
        ],
    ],

    'condition_types' => [
        'ip' => 'IP адрес',
        'cookie' => 'Cookie',
        'referer' => 'Реферер',
        'request_method' => 'HTTP метод',
        'user_agent' => 'User Agent',
        'header' => 'HTTP заглавие',
        'lang' => 'Език',
    ],

    'operators' => [
        'equals' => 'Равно на',
        'not_equals' => 'Не е равно на',
        'contains' => 'Съдържа',
        'not_contains' => 'Не съдържа',
    ],

    'buttons' => [
        'add' => 'Добави пренасочване',
        'save' => 'Запази',
        'edit' => 'Редактирай',
        'delete' => 'Изтрий',
        'actions' => 'Действия',
        'add_condition_group' => 'Добави група условия',
        'add_condition' => 'Добави условие',
        'remove_condition' => 'Премахни',
        'clear_cache' => 'Изчисти кеша',
    ],

    'messages' => [
        'save_success' => 'Пренасочването е запазено успешно.',
        'update_success' => 'Пренасочването е обновено успешно.',
        'delete_success' => 'Пренасочването е изтрито успешно.',
        'not_found' => 'Пренасочването не е намерено.',
        'cache_cleared' => 'Кешът на пренасочванията е изчистен успешно.',
        'route_conflict' => 'Внимание: URL ":url" конфликтува със съществуващ маршрут ":route". Пренасочването може да не работи, тъй като маршрутът има приоритет.',
        'from_url_required' => 'Полето «От URL» е задължително.',
        'to_url_required' => 'Полето «Към URL» е задължително.',
        'same_urls' => 'URL «От» и «Към» не могат да бъдат еднакви.',
    ],

    'empty' => [
        'title' => 'Все още няма пренасочвания',
        'sub' => 'Създайте първото пренасочване за управление на URL препращания',
    ],

    'confirms' => [
        'delete' => 'Сигурни ли сте, че искате да изтриете това пренасочване? Действието не може да бъде отменено.',
    ],

    'table' => [
        'from' => 'От',
        'to' => 'Към',
        'conditions' => 'Условия',
        'actions' => 'Действия',
    ],

    'modal' => [
        'create_title' => 'Създаване на пренасочване',
        'edit_title' => 'Редактиране на пренасочване',
        'conditions_title' => 'Условия за пренасочване',
        'conditions_help' => 'Между групите условия се използва логика ИЛИ, вътре в групата — И.',
        'group_label' => 'Група :number',
    ],

    'settings' => [
        'title' => 'Настройки',
        'cache_time' => [
            'label' => 'Продължителност на кеша (секунди)',
            'help' => 'Колко дълго правилата за пренасочване се кешират. Задайте 0 за деактивиране.',
            'placeholder' => '3600',
        ],
    ],

    'alert' => [
        'route_conflict_title' => 'Открит конфликт на маршрути',
    ],
];
