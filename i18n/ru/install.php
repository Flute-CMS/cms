<?php

return [
    'title' => 'Установка Flute CMS',
    'welcome' => [
        'title' => 'Добро пожаловать во Flute CMS',
        'get_started' => 'Начать установку',
    ],
    'requirements' => [
        'title' => 'Требования к системе',
        'description' => 'Пожалуйста, проверьте, чтобы все требования были выполнены перед началом установки.',
        'php' => 'PHP',
        'extensions' => 'Расширения',
        'directories' => 'Директории',
        'continue' => 'Продолжить',
        'writable' => 'Директория доступна для записи',
        'writable_error' => 'Директория недоступна для записи',
        'fix_errors' => 'Пожалуйста, исправьте все ошибки перед продолжением',
    ],
    'common' => [
        'next' => 'Следующий шаг',
        'back' => 'Предыдущий шаг',
        'finish' => 'Завершить установку',
        'finish_success' => 'Установка завершена успешно!',
    ],
    'flute_key' => [
        'title' => 'Лицензионный ключ',
        'description' => 'Пожалуйста, введите лицензионный ключ Flute CMS для продолжения установки.',
        'placeholder' => 'Введите лицензионный ключ',
        'hint' => 'Ключ по умолчанию для тестирования: Flute@Installer',
        'error_empty' => 'Лицензионный ключ обязателен',
        'error_invalid' => 'Введенный лицензионный ключ недействителен',
        'label' => 'Лицензионный ключ (опционально)',
        'success' => 'Лицензионный ключ успешно применен!',
    ],
    'database' => [
        'heading' => 'Настройка базы данных',
        'subheading' => 'Укажите параметры подключения к базе данных для установки Flute CMS',
        'driver' => 'Тип базы данных',
        'host' => 'Хост',
        'port' => 'Порт',
        'database' => 'Имя базы данных',
        'username' => 'Имя пользователя',
        'password' => 'Пароль',
        'prefix' => 'Префикс таблиц',
        'sqlite_note' => 'Для SQLite укажите только имя файла. Файл будет создан в директории storage/database/',
        'test_connection' => 'Проверить соединение',
        'connection_success' => 'Соединение с базой данных установлено успешно',
        'error_host_required' => 'Хост обязателен для заполнения',
        'error_database_required' => 'Имя базы данных обязательно для заполнения',
        'error_sqlite_dir' => 'Ошибка создания директории для SQLite',
        'error_driver_not_supported' => 'Выбранный драйвер базы данных не поддерживается',
    ],
    'admin_user' => [
        'heading' => 'Создание администратора',
        'subheading' => 'Создайте учетную запись администратора для управления Flute CMS',
        'name' => 'Полное имя',
        'email' => 'Электронная почта',
        'login' => 'Имя пользователя',
        'login_help' => 'Используется для входа в систему, должно быть уникальным',
        'password' => 'Пароль',
        'password_confirmation' => 'Подтверждение пароля',
        'create_user' => 'Создать администратора',
        'creation_success' => 'Администратор успешно создан! Теперь вы можете перейти к следующему шагу.',
        'error_name_required' => 'Полное имя обязательно для заполнения',
        'error_email_required' => 'Электронная почта обязательна для заполнения',
        'error_email_invalid' => 'Укажите корректный адрес электронной почты',
        'error_login_required' => 'Имя пользователя обязательно для заполнения',
        'error_password_required' => 'Пароль обязателен для заполнения',
        'error_password_length' => 'Пароль должен содержать не менее 8 символов',
        'error_password_mismatch' => 'Пароли не совпадают',
    ],
    'site_info' => [
        'heading' => 'Настройка сайта',
        'subheading' => 'Настройте основные параметры вашего сайта',
        'name' => 'Название сайта',
        'description' => 'Описание сайта',
        'keywords' => 'Ключевые слова',
        'keywords_help' => 'Разделяйте ключевые слова запятыми (например, игры, серверы, Flute)',
        'url' => 'URL сайта',
        'url_help' => 'Полный URL вашего сайта, включая http:// или https://',
        'timezone' => 'Часовой пояс',
        'footer_description' => 'Описание в футере',
        'footer_help' => 'Необязательный текст, который будет отображаться в футере сайта',
        'tab_basics' => 'Общая информация',
        'tab_seo' => 'SEO оптимизация',
        'basic_section' => 'Основная информация',
        'seo_section' => 'Поисковая оптимизация',
        'advanced_section' => 'Дополнительные настройки',
        'meta_title' => 'SEO заголовок',
        'meta_description' => 'SEO описание',
        'seo_preview' => 'Как это будет выглядеть в поиске',
        'seo_tips_title' => 'Советы по SEO',
        'seo_tips_content' => 'Используйте ключевые слова в начале заголовка. Оптимальная длина заголовка — 50-60 символов. Описание должно быть информативным и содержать призыв к действию в пределах 150-160 символов.',
    ],
    'site_settings' => [
        'heading' => 'Финальные настройки',
        'subheading' => 'Давайте настроим основные параметры вашего сайта, которые вы всегда сможете изменить позже',
        'tab_general' => 'Основные',
        'tab_security' => 'Безопасность',
        'general_section' => 'Настройки сайта',
        'appearance_section' => 'Внешний вид',
        'security_section' => 'Настройки безопасности',
        'cron_mode' => 'Режим крон-задач',
        'cron_mode_desc' => 'Включает режим крон-задач. Для работы крон-задач необходимо прописать в crontab',
        'maintenance_mode' => 'Режим обслуживания',
        'maintenance_mode_desc' => 'Сайт будет доступен только для администраторов, пока вы настраиваете его',
        'tips' => 'Подсказки интерфейса',
        'tips_desc' => 'Показывать полезные советы и подсказки при работе с админ-панелью',
        'share' => 'Делиться ошибками',
        'share_desc' => 'Будет отправлять ошибки CMS на сервер разработчиков',
        'flute_copyright' => 'Упоминание Flute',
        'flute_copyright_desc' => 'Небольшая ссылка на Flute CMS в футере сайта',
        'csrf_enabled' => 'Защита от CSRF-атак',
        'csrf_enabled_desc' => 'Защищает ваш сайт от подделки запросов. Рекомендуем оставить включенной',
        'convert_to_webp' => 'WebP изображения',
        'convert_to_webp_desc' => 'Автоматически конвертирует загружаемые изображения в формат WebP для ускорения сайта',
        'robots' => 'Настройки для поисковиков',
        'robots_desc' => 'Указывает поисковым системам, как работать с вашим сайтом',
        'robots_index_follow' => 'Индексировать сайт и переходить по ссылкам',
        'robots_index_nofollow' => 'Индексировать сайт, не переходя по ссылкам',
        'robots_noindex_follow' => 'Не индексировать сайт, но переходить по ссылкам',
        'robots_noindex_nofollow' => 'Не индексировать сайт и не переходить по ссылкам'
    ],
];
