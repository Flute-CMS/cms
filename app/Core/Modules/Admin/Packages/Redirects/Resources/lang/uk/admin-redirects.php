<?php

return [
    'title' => 'Редиректи',
    'description' => 'Управління URL-перенаправленнями з умовами',

    'fields' => [
        'from_url' => [
            'label' => 'Звідки',
            'placeholder' => '/old-page',
            'help' => 'URL-шлях для перенаправлення (наприклад /old-page)',
        ],
        'to_url' => [
            'label' => 'Куди',
            'placeholder' => '/new-page',
            'help' => 'URL призначення для перенаправлення',
        ],
        'conditions' => [
            'label' => 'Умови',
            'help' => 'Необов\'язкові умови, які мають бути виконані для спрацювання редиректу',
        ],
        'condition_type' => [
            'label' => 'Тип',
            'placeholder' => 'Оберіть тип умови',
        ],
        'condition_operator' => [
            'label' => 'Оператор',
            'placeholder' => 'Оберіть оператор',
        ],
        'condition_value' => [
            'label' => 'Значення',
            'placeholder' => 'Введіть значення',
        ],
    ],

    'condition_types' => [
        'ip' => 'IP-адреса',
        'cookie' => 'Cookie',
        'referer' => 'Реферер',
        'request_method' => 'HTTP-метод',
        'user_agent' => 'User Agent',
        'header' => 'HTTP-заголовок',
        'lang' => 'Мова',
    ],

    'operators' => [
        'equals' => 'Дорівнює',
        'not_equals' => 'Не дорівнює',
        'contains' => 'Містить',
        'not_contains' => 'Не містить',
    ],

    'buttons' => [
        'add' => 'Додати редирект',
        'save' => 'Зберегти',
        'edit' => 'Редагувати',
        'delete' => 'Видалити',
        'actions' => 'Дії',
        'add_condition_group' => 'Додати групу умов',
        'add_condition' => 'Додати умову',
        'remove_condition' => 'Видалити',
        'clear_cache' => 'Очистити кеш',
    ],

    'messages' => [
        'save_success' => 'Редирект успішно збережено.',
        'update_success' => 'Редирект успішно оновлено.',
        'delete_success' => 'Редирект успішно видалено.',
        'not_found' => 'Редирект не знайдено.',
        'cache_cleared' => 'Кеш редиректів успішно очищено.',
        'route_conflict' => 'Увага: URL ":url" конфліктує з існуючим маршрутом ":route". Редирект може не спрацювати, оскільки маршрут має пріоритет.',
        'from_url_required' => 'Поле «Звідки» обов\'язкове для заповнення.',
        'to_url_required' => 'Поле «Куди» обов\'язкове для заповнення.',
        'same_urls' => 'URL «Звідки» та «Куди» не можуть збігатися.',
    ],

    'empty' => [
        'title' => 'Редиректів поки немає',
        'sub' => 'Створіть перший редирект для керування перенаправленнями URL',
    ],

    'confirms' => [
        'delete' => 'Ви впевнені, що хочете видалити цей редирект? Цю дію неможливо скасувати.',
    ],

    'table' => [
        'from' => 'Звідки',
        'to' => 'Куди',
        'conditions' => 'Умови',
        'actions' => 'Дії',
    ],

    'modal' => [
        'create_title' => 'Створення редиректу',
        'edit_title' => 'Редагування редиректу',
        'conditions_title' => 'Умови редиректу',
        'conditions_help' => 'Між групами умов використовується логіка АБО, всередині групи — І.',
        'group_label' => 'Група :number',
    ],

    'settings' => [
        'title' => 'Налаштування',
        'cache_time' => [
            'label' => 'Час кешування (у секундах)',
            'help' => 'Як довго правила редиректів кешуються. Вкажіть 0 для вимкнення.',
            'placeholder' => '3600',
        ],
    ],

    'alert' => [
        'route_conflict_title' => 'Виявлено конфлікт маршрутів',
    ],
];
