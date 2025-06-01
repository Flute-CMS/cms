<?php

return [
    'title' => 'Модули',
    'modules_and_themes' => 'Модули и шаблоны',
    'description' => 'На этой странице вы можете управлять модулями Flute',
    'table' => [
        'name' => 'Название',
        'version' => 'Версия',
        'status' => 'Статус',
        'actions' => 'Действия'
    ],
    'status' => [
        'active' => 'Активен',
        'disabled' => 'Отключен',
        'not_installed' => 'Не установлено',
        'unknown' => 'Неизвестно'
    ],
    'actions' => [
        'update' => 'Обновить',
        'install' => 'Установить',
        'activate' => 'Активировать',
        'disable' => 'Отключить',
        'delete' => 'Удалить',
        'details' => 'Подробнее',
        'refresh_list' => 'Обновить список',
        'upload' => 'Загрузить модуль',
    ],
    'modal' => [
        'module_name' => 'Название модуля',
        'module_version' => 'Версия модуля',
        'module_description' => 'Описание модуля',
        'module_authors' => 'Автор(-ы)',
        'module_url' => 'Ссылка на модуль',
        'details_title' => 'Детали модуля: :name'
    ],
    'confirmations' => [
        'install' => 'Вы уверены, что хотите установить этот модуль?',
        'delete' => 'Вы уверены, что хотите удалить этот модуль?'
    ],
    'messages' => [
        'module_not_found' => 'Модуль не найден.',
        'list_updated' => 'Список модулей обновлен.',
        'installed' => 'Модуль \':name\' успешно установлен.',
        'install_error' => 'Ошибка при установке модуля: :message',
        'activated' => 'Модуль \':name\' успешно активирован.',
        'activation_error' => 'Ошибка при активации модуля: :message',
        'disabled' => 'Модуль \':name\' успешно отключен.',
        'disable_error' => 'Ошибка при отключении модуля: :message',
        'updated' => 'Модуль \':name\' успешно обновлен.',
        'update_error' => 'Ошибка при обновлении модуля: :message',
        'uninstalled' => 'Модуль \':name\' успешно удален.',
        'uninstall_error' => 'Ошибка при удалении модуля: :message'
    ],
    'dropzone' => [
        'title' => 'Загрузка архива модуля',
        'description' => 'Перетащите ZIP-архив сюда или нажмите для выбора',
        'select_file' => 'Выбрать файл',
        'upload_another' => 'Загрузить другой',
        'overlay_title' => 'Перетащите архив модуля сюда',
        'overlay_description' => 'Отпустите, чтобы загрузить архив модуля',
        'errors' => [
            'invalid_file' => 'Поддерживаются только ZIP-архивы',
            'unknown' => 'Произошла неизвестная ошибка',
            'network' => 'Произошла сетевая ошибка при загрузке',
            'no_file' => 'Архив модуля не загружен',
            'upload_failed' => 'Не удалось сохранить загруженный файл',
            'extract_failed' => 'Не удалось распаковать архив модуля',
            'invalid_structure' => 'Неверная структура модуля: файл module.json не найден',
            'no_module_json' => 'Отсутствует файл module.json',
            'invalid_module_json' => 'Неверный формат файла module.json или отсутствуют обязательные поля',
            'installation_failed' => 'Установка модуля не удалась: :error',
            'file_too_large' => 'Архив модуля слишком большой. Максимальный размер 50MB',
            'invalid_zip' => 'Загруженный файл не является корректным ZIP-архивом',
            'timeout' => 'Истекло время ожидания загрузки. Пожалуйста, повторите попытку',
            'invalid_module_key' => 'Идентификатор модуля содержит недопустимые символы',
        ],
    ],
]; 