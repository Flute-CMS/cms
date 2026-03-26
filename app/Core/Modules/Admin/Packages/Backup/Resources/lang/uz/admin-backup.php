<?php

return [
    'title' => 'Zaxira nusxalari',
    'description' => 'Modullar, mavzular va CMS zaxira nusxalarini boshqarish',

    'table' => [
        'type' => 'Turi',
        'name' => 'Nomi',
        'filename' => 'Fayl',
        'size' => 'Hajmi',
        'date' => 'Yaratilgan',
        'actions' => 'Amallar',
        'empty' => 'Hali zaxira nusxalari yo\'q',
    ],

    'types' => [
        'module' => 'Modul',
        'theme' => 'Mavzu',
        'modules' => 'Barcha modullar',
        'themes' => 'Barcha mavzular',
        'cms' => 'CMS',
        'full' => 'To\'liq zaxira',
        'vendor' => 'Vendor',
        'composer' => 'Composer',
    ],

    'metrics' => [
        'total_backups' => 'Jami zaxira nusxalari',
        'total_size' => 'Umumiy hajm',
    ],

    'actions' => [
        'backup_module' => 'Modulni zaxiralash',
        'backup_theme' => 'Mavzuni zaxiralash',
        'backup_all_modules' => 'Barcha modullarni zaxiralash',
        'backup_all_themes' => 'Barcha mavzularni zaxiralash',
        'backup_cms' => 'CMS yadrosini zaxiralash',
        'backup_full' => 'To\'liq zaxiralash',
        'download' => 'Yuklab olish',
        'delete' => 'O\'chirish',
        'restore' => 'Tiklash',
        'refresh' => 'Yangilash',
        'create_backup' => 'Zaxira nusxasini yaratish',
    ],

    'modal' => [
        'backup_module_title' => 'Modul zaxira nusxasini yaratish',
        'backup_theme_title' => 'Mavzu zaxira nusxasini yaratish',
        'select_module' => 'Modulni tanlang',
        'select_theme' => 'Mavzuni tanlang',
    ],

    'confirmations' => [
        'backup_all_modules' => 'Barcha modullarni zaxiralashni xohlaysizmi?',
        'backup_all_themes' => 'Barcha mavzularni zaxiralashni xohlaysizmi?',
        'backup_cms' => 'CMS yadrosini zaxiralashni xohlaysizmi?',
        'backup_full' => 'To\'liq zaxira nusxasini yaratishni xohlaysizmi? Bu biroz vaqt olishi mumkin.',
        'delete' => 'Ushbu zaxira nusxasini o\'chirishni xohlaysizmi?',
        'restore' => 'Ushbu zaxira nusxasidan tiklashni xohlaysizmi? Joriy fayllar qayta yoziladi.',
    ],

    'messages' => [
        'backup_created' => 'Zaxira nusxasi yaratildi: :filename',
        'backup_error' => 'Zaxiralash xatosi: :message',
        'backup_deleted' => 'Zaxira nusxasi o\'chirildi',
        'delete_error' => 'O\'chirish xatosi: :message',
        'download_error' => 'Yuklab olish xatosi: :message',
        'list_refreshed' => 'Ro\'yxat yangilandi',
        'restore_success' => 'Zaxira nusxasi muvaffaqiyatli tiklandi. Kesh tozalandi.',
        'restore_error' => 'Tiklash xatosi: :message',
    ],

    'errors' => [
        'module_not_found' => 'Modul topilmadi',
        'module_path_not_found' => 'Modul katalogi topilmadi',
        'theme_path_not_found' => 'Mavzu katalogi topilmadi',
        'modules_path_not_found' => 'Modullar katalogi topilmadi',
        'themes_path_not_found' => 'Mavzular katalogi topilmadi',
        'cannot_create_zip' => 'ZIP arxivini yaratib bo\'lmadi',
        'cannot_open_zip' => 'ZIP arxivini ochib bo\'lmadi',
        'backup_not_found' => 'Zaxira nusxasi topilmadi',
        'cannot_determine_destination' => 'Tiklash manzilini aniqlab bo\'lmadi',
        'unknown_backup_type' => 'Noma\'lum zaxira nusxasi turi',
    ],
];
