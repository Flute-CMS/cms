<?php

return [
    'title' => [
        'list' => 'API ключі',
        'description' => 'Керування API ключами для зовнішнього доступу',
        'create' => 'Створити API ключ',
        'edit' => 'Редагувати API ключ',
    ],
    'fields' => [
        'key' => [
            'label' => 'API ключ',
            'placeholder' => 'Введіть API ключ',
            'help' => 'Цей ключ буде використовуватися для автентифікації API',
        ],
        'name' => [
            'label' => 'Назва',
            'placeholder' => 'Введіть назву ключа',
            'help' => 'Ви можете використовувати цю назву для ідентифікації ключа',
        ],
        'permissions' => [
            'label' => 'Дозволи',
        ],
        'created_at' => 'Створено',
        'last_used_at' => 'Останнє використання',
        'never' => 'Ніколи',
    ],
    'buttons' => [
        'actions' => 'Дії',
        'add' => 'Додати ключ',
        'save' => 'Зберегти',
        'edit' => 'Редагувати',
        'delete' => 'Видалити',
    ],
    'confirms' => [
        'delete_key' => 'Ви впевнені, що хочете видалити цей API ключ?',
    ],
    'messages' => [
        'save_success' => 'API ключ успішно збережено.',
        'key_not_found' => 'API ключ не знайдено.',
        'no_permissions' => 'Будь ласка, виберіть хоча б один дозвіл.',
        'update_success' => 'API ключ успішно оновлено.',
        'update_error' => 'Помилка оновлення API ключа: :message',
        'delete_success' => 'API ключ успішно видалено.',
        'delete_error' => 'Помилка видалення API ключа: :message',
    ],

    'info_alert' => [
        'title' => 'Потрібен модуль API',
        'description' => 'API ключі дозволяють аутентифікувати запити, але для роботи API необхідно встановити модуль API з маркетплейсу.',
        'install_module' => 'Встановити модуль',
        'documentation' => 'Документація',
    ],
];
