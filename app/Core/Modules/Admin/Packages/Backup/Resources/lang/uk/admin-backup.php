<?php

return [
    'title' => 'Резервні копії',
    'description' => 'Управління резервними копіями модулів, тем та CMS',

    'table' => [
        'type' => 'Тип',
        'name' => 'Назва',
        'filename' => 'Файл',
        'size' => 'Розмір',
        'date' => 'Дата створення',
        'actions' => 'Дії',
        'empty' => 'Резервних копій поки що немає',
    ],

    'types' => [
        'module' => 'Модуль',
        'theme' => 'Тема',
        'modules' => 'Усі модулі',
        'themes' => 'Усі теми',
        'cms' => 'CMS',
        'full' => 'Повний бекап',
        'vendor' => 'Vendor',
        'composer' => 'Composer',
    ],

    'metrics' => [
        'total_backups' => 'Всього бекапів',
        'total_size' => 'Загальний розмір',
    ],

    'actions' => [
        'backup_module' => 'Бекап модуля',
        'backup_theme' => 'Бекап теми',
        'backup_all_modules' => 'Бекап усіх модулів',
        'backup_all_themes' => 'Бекап усіх тем',
        'backup_cms' => 'Бекап ядра CMS',
        'backup_full' => 'Повний бекап',
        'download' => 'Завантажити',
        'delete' => 'Видалити',
        'restore' => 'Відновити',
        'refresh' => 'Оновити',
        'create_backup' => 'Створити бекап',
    ],

    'modal' => [
        'backup_module_title' => 'Створити бекап модуля',
        'backup_theme_title' => 'Створити бекап теми',
        'select_module' => 'Виберіть модуль',
        'select_theme' => 'Виберіть тему',
    ],

    'confirmations' => [
        'backup_all_modules' => 'Ви впевнені, що хочете створити бекап усіх модулів?',
        'backup_all_themes' => 'Ви впевнені, що хочете створити бекап усіх тем?',
        'backup_cms' => 'Ви впевнені, що хочете створити бекап ядра CMS?',
        'backup_full' => 'Ви впевнені, що хочете створити повний бекап? Це може зайняти деякий час.',
        'delete' => 'Ви впевнені, що хочете видалити цю резервну копію?',
        'restore' => 'Ви впевнені, що хочете відновити з цієї резервної копії? Поточні файли будуть перезаписані.',
    ],

    'messages' => [
        'backup_created' => 'Резервну копію створено: :filename',
        'backup_error' => 'Помилка створення бекапа: :message',
        'backup_deleted' => 'Резервну копію видалено',
        'delete_error' => 'Помилка видалення бекапа: :message',
        'download_error' => 'Помилка завантаження: :message',
        'list_refreshed' => 'Список оновлено',
        'restore_success' => 'Резервну копію успішно відновлено. Кеш очищено.',
        'restore_error' => 'Помилка відновлення: :message',
    ],

    'errors' => [
        'module_not_found' => 'Модуль не знайдено',
        'module_path_not_found' => 'Директорію модуля не знайдено',
        'theme_path_not_found' => 'Директорію теми не знайдено',
        'modules_path_not_found' => 'Директорію модулів не знайдено',
        'themes_path_not_found' => 'Директорію тем не знайдено',
        'cannot_create_zip' => 'Не вдалося створити ZIP-архів',
        'cannot_open_zip' => 'Не вдалося відкрити ZIP-архів',
        'backup_not_found' => 'Резервну копію не знайдено',
        'cannot_determine_destination' => 'Не вдалося визначити шлях відновлення',
        'unknown_backup_type' => 'Невідомий тип резервної копії',
    ],
];
