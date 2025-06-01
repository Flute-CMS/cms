<?php

return [
    'title' => [
        'list' => 'API Ключи',
        'description' => 'Управление API ключами для внешнего доступа',
        'create' => 'Создать API ключ',
        'edit' => 'Редактировать API ключ'
    ],
    'fields' => [
        'key' => [
            'label' => 'API Ключ',
            'placeholder' => 'Введите API ключ',
            'help' => 'Этот ключ будет использоваться для аутентификации API'
        ],
        'name' => [
            'label' => 'Название',
            'placeholder' => 'Введите название ключа',
            'help' => 'Вы можете использовать это название для идентификации ключа'
        ],
        'permissions' => [
            'label' => 'Разрешения'
        ],
        'created_at' => 'Дата создания',
        'last_used_at' => 'Дата последнего использования',
        'never' => 'Никогда'
    ],
    'buttons' => [
        'actions' => 'Действия',
        'add' => 'Добавить ключ',
        'save' => 'Сохранить',
        'edit' => 'Редактировать',
        'delete' => 'Удалить'
    ],
    'confirms' => [
        'delete_key' => 'Вы уверены, что хотите удалить этот API ключ?'
    ],
    'messages' => [
        'save_success' => 'API ключ успешно сохранен.',
        'key_not_found' => 'API ключ не найден.',
        'no_permissions' => 'Необходимо выбрать хотя бы одно разрешение.',
        'update_success' => 'API ключ успешно обновлен.',
        'update_error' => 'Ошибка при обновлении API ключа: :message',
        'delete_success' => 'API ключ успешно удален.',
        'delete_error' => 'Ошибка при удалении API ключа: :message'
    ]
];