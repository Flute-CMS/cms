<?php

return [
    'title' => 'Резервные копии',
    'description' => 'Управление резервными копиями модулей, тем и CMS',

    'table' => [
        'type' => 'Тип',
        'name' => 'Название',
        'filename' => 'Файл',
        'size' => 'Размер',
        'date' => 'Дата создания',
        'actions' => 'Действия',
        'empty' => 'Резервных копий пока нет',
    ],

    'types' => [
        'module' => 'Модуль',
        'theme' => 'Тема',
        'modules' => 'Все модули',
        'themes' => 'Все темы',
        'cms' => 'CMS',
        'full' => 'Полный бекап',
        'vendor' => 'Vendor',
        'composer' => 'Composer',
    ],

    'metrics' => [
        'total_backups' => 'Всего бекапов',
        'total_size' => 'Общий размер',
    ],

    'actions' => [
        'backup_module' => 'Бекап модуля',
        'backup_theme' => 'Бекап темы',
        'backup_all_modules' => 'Бекап всех модулей',
        'backup_all_themes' => 'Бекап всех тем',
        'backup_cms' => 'Бекап ядра CMS',
        'backup_full' => 'Полный бекап',
        'download' => 'Скачать',
        'delete' => 'Удалить',
        'restore' => 'Восстановить',
        'refresh' => 'Обновить',
        'create_backup' => 'Создать бекап',
    ],

    'modal' => [
        'backup_module_title' => 'Создать бекап модуля',
        'backup_theme_title' => 'Создать бекап темы',
        'select_module' => 'Выберите модуль',
        'select_theme' => 'Выберите тему',
    ],

    'confirmations' => [
        'backup_all_modules' => 'Вы уверены, что хотите создать бекап всех модулей?',
        'backup_all_themes' => 'Вы уверены, что хотите создать бекап всех тем?',
        'backup_cms' => 'Вы уверены, что хотите создать бекап ядра CMS?',
        'backup_full' => 'Вы уверены, что хотите создать полный бекап? Это может занять некоторое время.',
        'delete' => 'Вы уверены, что хотите удалить эту резервную копию?',
        'restore' => 'Вы уверены, что хотите восстановить из этой резервной копии? Текущие файлы будут перезаписаны.',
    ],

    'messages' => [
        'backup_created' => 'Резервная копия создана: :filename',
        'backup_error' => 'Ошибка создания бекапа: :message',
        'backup_deleted' => 'Резервная копия удалена',
        'delete_error' => 'Ошибка удаления бекапа: :message',
        'download_error' => 'Ошибка скачивания: :message',
        'list_refreshed' => 'Список обновлён',
        'restore_success' => 'Резервная копия успешно восстановлена. Кэш очищен.',
        'restore_error' => 'Ошибка восстановления: :message',
    ],

    'errors' => [
        'module_not_found' => 'Модуль не найден',
        'module_path_not_found' => 'Директория модуля не найдена',
        'theme_path_not_found' => 'Директория темы не найдена',
        'modules_path_not_found' => 'Директория модулей не найдена',
        'themes_path_not_found' => 'Директория тем не найдена',
        'cannot_create_zip' => 'Не удалось создать ZIP-архив',
        'cannot_open_zip' => 'Не удалось открыть ZIP-архив',
        'backup_not_found' => 'Резервная копия не найдена',
        'cannot_determine_destination' => 'Не удалось определить путь восстановления',
        'unknown_backup_type' => 'Неизвестный тип резервной копии',
    ],
];
