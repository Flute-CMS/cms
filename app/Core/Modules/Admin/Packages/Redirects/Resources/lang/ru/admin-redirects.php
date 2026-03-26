<?php

return [
    'title' => 'Редиректы',
    'description' => 'Управление URL-перенаправлениями с условиями',

    'fields' => [
        'from_url' => [
            'label' => 'Откуда',
            'placeholder' => '/old-page',
            'help' => 'URL-путь для перенаправления (например /old-page)',
        ],
        'to_url' => [
            'label' => 'Куда',
            'placeholder' => '/new-page',
            'help' => 'URL назначения для перенаправления',
        ],
        'conditions' => [
            'label' => 'Условия',
            'help' => 'Необязательные условия, которые должны быть выполнены для срабатывания редиректа',
        ],
        'condition_type' => [
            'label' => 'Тип',
            'placeholder' => 'Выберите тип условия',
        ],
        'condition_operator' => [
            'label' => 'Оператор',
            'placeholder' => 'Выберите оператор',
        ],
        'condition_value' => [
            'label' => 'Значение',
            'placeholder' => 'Введите значение',
        ],
    ],

    'condition_types' => [
        'ip' => 'IP-адрес',
        'cookie' => 'Cookie',
        'referer' => 'Реферер',
        'request_method' => 'HTTP-метод',
        'user_agent' => 'User Agent',
        'header' => 'HTTP-заголовок',
        'lang' => 'Язык',
    ],

    'operators' => [
        'equals' => 'Равно',
        'not_equals' => 'Не равно',
        'contains' => 'Содержит',
        'not_contains' => 'Не содержит',
    ],

    'buttons' => [
        'add' => 'Добавить редирект',
        'save' => 'Сохранить',
        'edit' => 'Редактировать',
        'delete' => 'Удалить',
        'actions' => 'Действия',
        'add_condition_group' => 'Добавить группу условий',
        'add_condition' => 'Добавить условие',
        'remove_condition' => 'Удалить',
        'clear_cache' => 'Очистить кэш',
    ],

    'messages' => [
        'save_success' => 'Редирект успешно сохранён.',
        'update_success' => 'Редирект успешно обновлён.',
        'delete_success' => 'Редирект успешно удалён.',
        'not_found' => 'Редирект не найден.',
        'cache_cleared' => 'Кэш редиректов успешно очищен.',
        'route_conflict' => 'Внимание: URL ":url" конфликтует с существующим маршрутом ":route". Редирект может не сработать, так как маршрут имеет приоритет.',
        'from_url_required' => 'Поле «Откуда» обязательно для заполнения.',
        'to_url_required' => 'Поле «Куда» обязательно для заполнения.',
        'same_urls' => 'URL «Откуда» и «Куда» не могут совпадать.',
    ],

    'empty' => [
        'title' => 'Редиректов пока нет',
        'sub' => 'Создайте первый редирект для управления перенаправлениями URL',
    ],

    'confirms' => [
        'delete' => 'Вы уверены, что хотите удалить этот редирект? Это действие необратимо.',
    ],

    'table' => [
        'from' => 'Откуда',
        'to' => 'Куда',
        'conditions' => 'Условия',
        'actions' => 'Действия',
    ],

    'modal' => [
        'create_title' => 'Создание редиректа',
        'edit_title' => 'Редактирование редиректа',
        'conditions_title' => 'Условия редиректа',
        'conditions_help' => 'Между группами условий используется логика ИЛИ, внутри группы — И.',
        'group_label' => 'Группа :number',
    ],

    'settings' => [
        'title' => 'Настройки',
        'cache_time' => [
            'label' => 'Время кэширования (в секундах)',
            'help' => 'Как долго правила редиректов кэшируются. Укажите 0 для отключения.',
            'placeholder' => '3600',
        ],
    ],

    'alert' => [
        'route_conflict_title' => 'Обнаружен конфликт маршрутов',
    ],
];
