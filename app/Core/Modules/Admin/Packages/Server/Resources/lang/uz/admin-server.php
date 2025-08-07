<?php

return [
    'search_servers' => 'Serverlar Qidirish',
    'title' => [
        'list' => 'Serverlar',
        'edit' => 'Serverni Tahrirlash',
        'create' => 'Server Qoʻshish',
        'description' => 'Flute ga qoʻshilgan barcha serverlar bu yerda koʻrsatilgan',
        'main_info' => 'Asosiy Maʻlumotlar',
        'actions' => 'Amallar',
        'actions_description' => 'Server ustidagi amallar',
        'integrations' => 'Integratsiyalar',
    ],

    'tabs' => [
        'main' => 'Asosiy',
        'db_connections' => 'DB Ulanishlar',
    ],

    'fields' => [
        'name' => [
            'label' => 'Nomi',
            'placeholder' => 'Server nomini kiriting',
        ],
        'ip' => [
            'label' => 'IP Manzil',
            'placeholder' => '127.0.0.1',
        ],
        'port' => [
            'label' => 'Port',
            'placeholder' => '27015',
        ],
        'mod' => [
            'label' => 'Oʻyin',
            'placeholder' => 'Oʻyinni tanlang',
        ],
        'rcon' => [
            'label' => 'RCON Paroli',
            'placeholder' => 'RCON parolini kiriting',
            'help' => 'Serverni masofadan boshqarish uchun parol',
        ],
        'display_ip' => [
            'label' => 'Koʻrsatiladigan IP',
            'placeholder' => '127.0.0.1:27015',
            'help' => 'Foydalanuvchilarga koʻrsatiladigan IP manzil',
        ],
        'ranks' => [
            'label' => 'Rank Paketi',
            'placeholder' => 'Rank paketini tanlang',
        ],
        'ranks_format' => [
            'label' => 'Rank Fayl Formati',
            'placeholder' => 'Rank fayl formatini tanlang',
        ],
        'ranks_premier' => [
            'label' => 'Premier Ranklar',
            'placeholder' => 'Server premier ranklarni ishlatishi kerakmi',
        ],
        'enabled' => [
            'label' => 'Faol',
            'help' => 'Server ommaviy roʻyxatda koʻrinishi kerakmi',
        ],
        'created_at' => 'Yaratilgan Sana',
    ],

    'status' => [
        'active' => 'Faol',
        'inactive' => 'Nofaol',
    ],

    'db_connection' => [
        'title' => 'DB Ulanishlar',
        'fields' => [
            'mod' => [
                'label' => 'Mod',
                'placeholder' => 'Modini kiriting',
                'help' => 'Ushbu server uchun ishlatish uchun plaginni tanlang',
            ],
            'dbname' => [
                'label' => 'Maʻlumotlar Bazasi',
                'placeholder' => 'Maʻlumotlar bazasi nomini kiriting',
            ],
            'driver' => [
                'label' => 'Drayver',
                'placeholder' => 'Drayverni tanlang',
                'custom' => 'Maxsus',
            ],
            'additional' => [
                'label' => 'Qoʻshimcha Sozlamalar',
                'placeholder' => 'Qoʻshimcha sozlamalarni kiriting',
            ],
            'params' => 'Param.',
            'custom_driver_name' => [
                'label' => 'Drayver Nomi',
                'placeholder' => 'Drayver nomini kiriting',
            ],
            'json_settings' => [
                'label' => 'JSON Sozlamalar',
                'placeholder' => 'Sozlamalarni JSON formatida kiriting',
                'help' => 'Ixtiyoriy JSON sozlamalarini kiriting',
            ],
        ],
        'add' => [
            'title' => 'DB Ulanish Qoʻshish',
            'button' => 'Ulanish Qoʻshish',
        ],
        'edit' => [
            'title' => 'DB Ulanishni Tahrirlash',
        ],
        'delete' => [
            'confirm' => 'Ushbu ulanishni oʻchirishga ishonchingiz komilmi?',
        ],
    ],

    'db_drivers' => [
        'default' => [
            'name' => 'Standart',
            'fields' => [
                'connection' => [
                    'label' => 'Ulanish',
                    'placeholder' => 'DB ulanishini tanlang',
                    'help' => 'Konfiguratsiyangizdan maʻlumotlar bazasi ulanishini tanlang',
                ],
                'table_prefix' => [
                    'label' => 'Jadval Prefiksi',
                    'placeholder' => 'Jadval prefiksini kiriting',
                    'help' => 'Maʻlumotlar bazasi jadvallari uchun prefiks',
                ],
            ],
        ],
        'statistics' => [
            'name' => 'Statistika',
            'fields' => [
                'connection' => [
                    'label' => 'Ulanish',
                    'placeholder' => 'DB ulanishini tanlang',
                    'help' => 'Konfiguratsiyangizdan maʻlumotlar bazasi ulanishini tanlang',
                ],
                'table_prefix' => [
                    'label' => 'Jadval Prefiksi',
                    'placeholder' => 'Jadval prefiksini kiriting',
                    'help' => 'Maʻlumotlar bazasi jadvallari uchun prefiks',
                ],
                'player_table' => [
                    'label' => 'Oʻyinchi Jadvali',
                    'placeholder' => 'Oʻyinchi jadvali nomini kiriting',
                    'help' => 'Oʻyinchi maʻlumotlarini oʻz ichiga olgan jadval',
                ],
                'steam_id_field' => [
                    'label' => 'Steam ID Maydoni',
                    'placeholder' => 'Steam ID maydon nomini kiriting',
                    'help' => 'Steam ID ni oʻz ichiga olgan maydon',
                ],
                'name_field' => [
                    'label' => 'Ism Maydoni',
                    'placeholder' => 'Ism maydon nomini kiriting',
                    'help' => 'Oʻyinchi ismini oʻz ichiga olgan maydon',
                ],
            ],
        ],
        'no_drivers' => [
            'title' => 'DB Drayverlari Mavjud Emas',
            'description' => 'Roʻyxatdan oʻtgan maʻlumotlar bazasi drayverlari topilmadi. Iltimos, administratorga murojaat qiling.',
        ],
    ],

    'mods' => [
        'custom_settings_name' => [
            'title' => 'Drayver Nomi',
            'placeholder' => 'Drayver nomini kiriting',
        ],
        'custom_settings_json' => [
            'title' => 'Sozlamalar JSON',
            'placeholder' => 'JSON sozlamalarini kiriting',
        ],
        'custom_alert' => [
            'title' => 'Ogohlantirish!',
            'description' => 'Maxsus sozlamalarni kiritish ehtiyotkorlik talab qiladi! Agar ishonchingiz komil boʻlmasa, maxsus sozlamalar qoʻshmang!',
        ],
        'custom' => 'Maxsus',
    ],

    'buttons' => [
        'add' => 'Qoʻshish',
        'save' => 'Saqlash',
        'cancel' => 'Bekor Qilish',
        'delete' => 'Oʻchirish',
        'edit' => 'Tahrirlash',
        'actions' => 'Amallar',
    ],

    'messages' => [
        'server_not_found' => 'Server topilmadi.',
        'connection_not_found' => 'Ulanish topilmadi.',
        'save_success' => 'Server muvaffaqiyatli saqlandi.',
        'delete_success' => 'Server muvaffaqiyatli oʻchirildi.',
        'connection_add_success' => 'Ulanish muvaffaqiyatli qoʻshildi.',
        'connection_update_success' => 'Ulanish muvaffaqiyatli yangilandi.',
        'connection_delete_success' => 'Ulanish muvaffaqiyatli oʻchirildi.',
        'save_server_first' => 'Iltimos, avval serverni saqlang.',
        'invalid_driver_settings' => 'Notoʻgʻri drayver sozlamalari.',
        'no_permission.manage' => 'Serverlarni boshqarish uchun ruxsatingiz yoʻq.',
        'no_permission.delete' => 'Serverlarni oʻchirish uchun ruxsatingiz yoʻq.',
        'invalid_json' => 'Notoʻgʻri JSON formati.',
        'server_deleted' => 'Server muvaffaqiyatli oʻchirib tashlandi.',
        'server_updated' => 'Server muvaffaqiyatli yangilandi.',
        'server_created' => 'Server muvaffaqiyatli yaratildi.',
        'save_not_for_db_connections' => 'Saqlash faqat asosiy server maʻlumotlari uchun.',
        'invalid_ip' => 'Portsiz toʻgʻri IP manzilni kiriting.',
    ],

    'confirms' => [
        'delete_server' => 'Ushbu serverni oʻchirishga ishonchingiz komilmi? Bu amalni bekor qilib boʻlmaydi.',
    ],
];
