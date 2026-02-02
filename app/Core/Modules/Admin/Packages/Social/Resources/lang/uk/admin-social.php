<?php

return [
    'title' => [
        'social' => 'Соціальні мережі',
        'description' => 'На цій сторінці ви можете налаштувати соціальні мережі для автентифікації',
        'edit' => 'Редагувати соціальну мережу: :name',
        'create' => 'Додати соціальну мережу',
    ],
    'table' => [
        'social' => 'Соціальна мережа',
        'cooldown' => 'Cooldown',
        'registration' => 'Реєстрація',
        'status' => 'Статус',
        'actions' => 'Дії',
    ],
    'fields' => [
        'icon' => [
            'label' => 'Іконка',
            'placeholder' => 'наприклад: ph.regular.steam',
        ],
        'allow_register' => [
            'label' => 'Дозволити реєстрацію',
            'help' => 'Можна реєструватися через цю соціальну мережу',
        ],
        'cooldown_time' => [
            'label' => 'Час cooldown',
            'help' => 'Приклад: 3600 (секунд, дорівнює 1 годині)',
            'small' => 'Приклад: 3600 секунд (1 година)',
            'placeholder' => '3600 секунд',
            'popover' => 'Час між видаленням соціального зв\'язку та можливістю додати його знову',
        ],
        'redirect_uri' => [
            'first' => 'Перший URI',
            'second' => 'Другий URI',
        ],
        'driver' => [
            'label' => 'Драйвер автентифікації',
            'placeholder' => 'Виберіть драйвер',
        ],
        'client_id' => [
            'label' => 'Client ID',
        ],
        'client_secret' => [
            'label' => 'Client Secret',
        ],
    ],
    'buttons' => [
        'add' => 'Додати',
        'save' => 'Зберегти',
        'edit' => 'Редагувати',
        'delete' => 'Видалити',
        'enable' => 'Увімкнути',
        'disable' => 'Вимкнути',
    ],
    'status' => [
        'active' => 'Активна',
        'inactive' => 'Неактивна',
    ],
    'confirms' => [
        'delete' => 'Ви впевнені, що хочете видалити цю соціальну мережу?',
    ],
    'messages' => [
        'save_success' => 'Соціальну мережу успішно збережено.',
        'save_error' => 'Помилка збереження: :message',
        'delete_success' => 'Соціальну мережу успішно видалено.',
        'delete_error' => 'Помилка видалення: :message',
        'toggle_success' => 'Статус соціальної мережі успішно змінено.',
        'toggle_error' => 'Помилка зміни статусу.',
        'not_found' => 'Соціальну мережу не знайдено.',
    ],
    'edit' => [
        'default' => 'Драйвер :driver не протестований. Він може не працювати коректно. Вам потрібно налаштувати параметри вручну.',
        'steam_success' => 'Все гаразд, налаштування не потрібне.',
        'steam_error' => 'Не встановлено STEAM API ключ. Налаштуйте його в <a href="/admin/main-settings" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">налаштуваннях</a>.',
    ],
    'no_drivers' => 'Немає доступних драйверів.',
];
