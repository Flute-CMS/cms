<?php

return [
    'title' => [
        'social' => 'Социальные сети',
        'description' => 'На этой странице вы можете настроить социальные сети через которые будет происходить авторизация',
        'edit' => 'Изменение соц.сети :name',
        'create' => 'Добавление социальной сети',
    ],
    'table' => [
        'social' => 'Соц.сеть',
        'cooldown' => 'Время ожидания',
        'registration' => 'Регистрация',
        'status' => 'Статус',
        'actions' => 'Действия',
    ],
    'fields' => [
        'icon' => [
            'label' => 'Иконка',
            'placeholder' => 'К примеру: ph.regular.steam',
        ],
        'allow_register' => [
            'label' => 'Разрешить регистрацию',
            'help' => 'Можно ли зарегистрироваться через эту социальную сеть',
        ],
        'cooldown_time' => [
            'label' => 'Время ожидания',
            'help' => 'Пример 3600. (Время указывается в секундах. Это будет 1 час)',
            'small' => 'Пример 3600. (Время указывается в секундах. Это будет 1 час)',
            'placeholder' => '3600 секунд',
            'popover' => 'Это время, которое будет проходить между удалением соц.сети из аккаунта и возможностью добавить её снова',
        ],
        'redirect_uri' => [
            'first' => 'Первый URI',
            'second' => 'Второй URI',
        ],
        'driver' => [
            'label' => 'Драйвер авторизации',
            'placeholder' => 'Выберите драйвер',
        ],
        'client_id' => [
            'label' => 'Client ID',
        ],
        'client_secret' => [
            'label' => 'Client Secret',
        ],
    ],
    'buttons' => [
        'add' => 'Добавить',
        'save' => 'Сохранить',
        'edit' => 'Редактировать',
        'delete' => 'Удалить',
        'enable' => 'Включить',
        'disable' => 'Отключить',
    ],
    'status' => [
        'active' => 'Активно',
        'inactive' => 'Неактивно',
    ],
    'confirms' => [
        'delete' => 'Вы уверены, что хотите удалить эту социальную сеть?',
    ],
    'messages' => [
        'save_success' => 'Социальная сеть успешно сохранена.',
        'save_error' => 'Ошибка при сохранении: :message',
        'delete_success' => 'Социальная сеть успешно удалена.',
        'delete_error' => 'Ошибка при удалении: :message',
        'toggle_success' => 'Статус социальной сети успешно изменен.',
        'toggle_error' => 'Ошибка при изменении статуса.',
        'not_found' => 'Социальная сеть не найдена.',
    ],
    'edit' => [
        'default' => 'Драйвер :driver не проверялся. Возможно, он работает некорректно. Так же необходимо написать все параметры вручную.',
        'discord' => 'О том, как настроить авторизацию через Discord, можно узнать в <a class="ms-0" href="https://docs.flute-cms.com/social-auth/discord" target="_blank">документации</a>',
        'discord_token' => 'Токен бота',
        'discord_token_help' => 'Токен необходим для <a class="accent" href="#">синхронизации ролей с Discord</a>. Он не является обязательным',
        'steam_success' => 'У вас все хорошо, ничего настраивать не нужно.',
        'steam_error' => 'У вас не установлен STEAM API ключ. Установите его в <a href="/admin/main-settings" yoyo:ignore hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true" class="ms-0">настройках</a>.',
        'telegram' => 'О том, как настроить авторизацию через Telegram, можно узнать в <a class="ms-0" href="https://docs.flute-cms.com/social-auth/telegram" target="_blank">документации</a>',
        'telegram_token' => 'Токен бота',
        'telegram_token_placeholder' => '1234546',
        'telegram_bot_name' => 'Имя бота',
        'telegram_bot_name_placeholder' => 'Например: MyAwesomeBot',
    ],
    'no_drivers' => 'Нет доступных драйверов.',
];
