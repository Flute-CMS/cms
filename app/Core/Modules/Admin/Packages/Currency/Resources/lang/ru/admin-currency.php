<?php

return [
    'title' => [
        'list' => 'Валюты',
        'edit' => 'Редактирование валюты',
        'create' => 'Создание валюты',
        'description' => 'На этой странице представлены все валюты системы',
        'main_info' => 'Основная информация',
        'actions' => 'Действия',
        'actions_description' => 'Действия над валютой',
    ],

    'fields' => [
        'name' => [
            'label' => 'Название',
            'placeholder' => 'Введите название валюты',
        ],
        'code' => [
            'label' => 'Код',
            'placeholder' => 'Введите код валюты',
            'help' => 'Уникальный код валюты (например: USD, EUR, RUB)',
        ],
        'minimum_value' => [
            'label' => 'Минимальная сумма',
            'placeholder' => 'Введите минимальную сумму',
            'help' => 'Минимальная сумма для пополнения в этой валюте',
        ],
        'rate' => [
            'label' => 'Курс',
            'placeholder' => 'Введите курс валюты',
            'help' => 'Курс относительно базовой валюты',
        ],
        'enabled' => [
            'label' => 'Активна',
            'help' => 'Активная валюта доступна для использования в системе',
        ],
        'created_at' => 'Дата создания',
        'updated_at' => 'Дата обновления',
    ],

    'status' => [
        'active' => 'Активна',
        'inactive' => 'Не активна',
        'default' => 'По умолчанию',
    ],

    'buttons' => [
        'add' => 'Добавить валюту',
        'save' => 'Сохранить',
        'cancel' => 'Отмена',
        'delete' => 'Удалить',
        'edit' => 'Редактировать',
        'actions' => 'Действия',
        'update_rates' => 'Обновить курсы',
    ],

    'messages' => [
        'currency_not_found' => 'Валюта не найдена.',
        'save_success' => 'Валюта успешно сохранена.',
        'delete_success' => 'Валюта успешно удалена.',
        'update_rates_success' => 'Курсы валют успешно обновлены.',
        'default_currency_delete' => 'Нельзя удалить валюту по умолчанию.',
        'no_permission' => [
            'manage' => 'У вас нет прав для управления валютами.',
            'delete' => 'У вас нет прав для удаления валют.',
        ],
    ],

    'confirms' => [
        'delete_currency' => 'Вы уверены, что хотите удалить эту валюту? Это действие необратимо.',
        'set_default' => 'Вы уверены, что хотите сделать эту валюту основной? Все курсы будут пересчитаны.',
    ],
];