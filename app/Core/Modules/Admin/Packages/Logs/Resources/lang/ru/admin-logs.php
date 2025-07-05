<?php

return [
    'title' => 'Журнал событий',
    'description' => 'Просмотр и управление логами системы',

    'labels' => [
        'select_file'        => 'Выберите файл логов',
        'log_file'           => 'Файл',
        'size'               => 'Размер',
        'modified'           => 'Изменен',
        'level'              => 'Уровень',
        'date'               => 'Дата',
        'channel'            => 'Канал',
        'message'            => 'Сообщение',
        'details'            => 'Детали',
        'filter_by_level'    => 'Все уровни',
        'no_logs'            => 'Логи не найдены',
        'no_logs_description' => 'Записи логов не найдены для выбранных фильтров',
        'main'               => 'Системное',
        'entries'            => 'записей',
        'entries_loaded'     => 'записей загружено',
        'context_data'       => 'Контекстные данные',
        'search_placeholder' => 'Поиск в логах...',
        'of'                 => 'из',
    ],

    'level_labels' => [
        'debug' => 'Отладка',
        'info' => 'Информация',
        'notice' => 'Уведомление',
        'warning' => 'Предупреждение',
        'error' => 'Ошибка',
        'critical' => 'Критическая',
        'alert' => 'Тревога',
        'emergency' => 'Экстренная',
    ],

    'refresh' => 'Обновить',
    'download' => 'Скачать с информацией',
    'all_levels' => 'Все уровни',
    'show_context' => 'Код',
    'show_more' => 'Еще',
    'show_less' => 'Скрыть',

    'clear_log' => 'Очистить лог',
    'clear_confirm' => 'Вы уверены, что хотите очистить данный лог файл?',
    'cleared_success' => 'Лог файл успешно очищен',
    'cleared_error' => 'Ошибка при очистке лог файла',

    'export_error' => 'Ошибка при экспорте лог файла',
    'export_success' => 'Файл логов подготовлен к скачиванию',

    'no_log_selected'    => 'Не выбран файл логов',
    'auto_refresh_enabled' => 'Автообновление включено',
    'auto_refresh_disabled' => 'Автообновление отключено',
    'load_more'          => 'Загрузить ещё',
    'search_logs'        => 'Поиск в логах',
    'page'               => 'Страница',
    'previous'           => 'Назад',
    'next'               => 'Далее',
];
