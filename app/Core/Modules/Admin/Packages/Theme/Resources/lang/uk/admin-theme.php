<?php

return [
    'title' => [
        'themes' => 'Теми',
        'description' => 'На цій сторінці ви можете керувати темами та їх налаштуваннями',
        'edit' => 'Редагувати тему: :name',
        'create' => 'Додати тему',
    ],
    'table' => [
        'name' => 'Назва',
        'version' => 'Версія',
        'status' => 'Статус',
        'actions' => 'Дії',
    ],
    'fields' => [
        'name' => [
            'label' => 'Назва',
            'placeholder' => 'Введіть назву теми',
        ],
        'version' => [
            'label' => 'Версія',
            'placeholder' => 'Введіть версію теми',
        ],
        'enabled' => [
            'label' => 'Увімкнено',
            'help' => 'Увімкнути або вимкнути цю тему',
        ],
        'description' => [
            'label' => 'Опис',
            'placeholder' => 'Введіть опис теми',
        ],
        'author' => [
            'label' => 'Автор',
            'placeholder' => 'Введіть автора теми',
        ],
    ],
    'buttons' => [
        'save' => 'Зберегти',
        'edit' => 'Редагувати',
        'delete' => 'Видалити',
        'enable' => 'Увімкнути',
        'disable' => 'Вимкнути',
        'refresh' => 'Оновити список тем',
        'details' => 'Деталі',
        'install' => 'Встановити',
    ],
    'status' => [
        'active' => 'Активна',
        'inactive' => 'Неактивна',
        'not_installed' => 'Не встановлена',
    ],
    'confirms' => [
        'delete' => 'Ви впевнені, що хочете видалити цю тему?',
        'install' => 'Ви впевнені, що хочете встановити цю тему?',
    ],
    'messages' => [
        'save_success' => 'Тему успішно збережено.',
        'save_error' => 'Помилка збереження теми: :message',
        'delete_success' => 'Тему успішно видалено.',
        'delete_error' => 'Помилка видалення теми: :message',
        'toggle_success' => 'Статус теми успішно змінено.',
        'toggle_error' => 'Помилка зміни статусу теми.',
        'not_found' => 'Тему не знайдено.',
        'refresh_success' => 'Список тем успішно оновлено.',
        'install_success' => 'Тему успішно встановлено.',
        'install_error' => 'Помилка встановлення теми: :message',
        'enable_success' => 'Тему успішно увімкнено.',
        'enable_error' => 'Помилка увімкнення теми: :message',
        'disable_success' => 'Тему успішно вимкнено.',
        'disable_error' => 'Помилка вимкнення теми: :message',
    ],
];
