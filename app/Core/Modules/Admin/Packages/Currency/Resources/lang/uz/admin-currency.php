<?php

return [
    'title' => [
        'list' => 'Valyutalar',
        'edit' => 'Valyutani Tahrirlash',
        'create' => 'Valyuta Yaratish',
        'description' => 'Bu sahifa barcha tizim valyutalarini koʻrsatadi',
        'main_info' => 'Asosiy Maʻlumotlar',
        'actions' => 'Amallar',
        'actions_description' => 'Valyuta ustidagi amallar',
    ],

    'fields' => [
        'name' => [
            'label' => 'Nomi',
            'placeholder' => 'Valyuta nomini kiriting',
        ],
        'code' => [
            'label' => 'Kodi',
            'placeholder' => 'Valyuta kodini kiriting',
            'help' => 'Noyob valyuta kodi (masalan: USD, EUR, RUB)',
        ],
        'minimum_value' => [
            'label' => 'Minimal Miqdor',
            'placeholder' => 'Minimal miqdorni kiriting',
            'help' => 'Ushbu valyuta uchun minimal toʻldirish miqdori',
        ],
        'rate' => [
            'label' => 'Kurs',
            'placeholder' => 'Valyuta kursini kiriting',
            'help' => 'Asosiy valyutaga nisbatan kurs',
        ],
        'enabled' => [
            'label' => 'Faol',
            'help' => 'Faol valyuta tizimda foydalanish uchun mavjud',
        ],
        'created_at' => 'Yaratilgan Sana',
        'updated_at' => 'Yangilangan Sana',
    ],

    'status' => [
        'active' => 'Faol',
        'inactive' => 'Nofaol',
        'default' => 'Asosiy',
    ],

    'buttons' => [
        'add' => 'Valyuta Qoʻshish',
        'save' => 'Saqlash',
        'cancel' => 'Bekor Qilish',
        'delete' => 'Oʻchirish',
        'edit' => 'Tahrirlash',
        'actions' => 'Amallar',
        'update_rates' => 'Kurslarni Yangilash',
    ],

    'messages' => [
        'currency_not_found' => 'Valyuta topilmadi.',
        'save_success' => 'Valyuta muvaffaqiyatli saqlandi.',
        'delete_success' => 'Valyuta muvaffaqiyatli oʻchirildi.',
        'update_rates_success' => 'Valyuta kurslari muvaffaqiyatli yangilandi.',
        'default_currency_delete' => 'Asosiy valyutani oʻchirib boʻlmaydi.',
        'no_permission.manage' => 'Valyutalarni boshqarish uchun ruxsatingiz yoʻq.',
        'no_permission.delete' => 'Valyutalarni oʻchirish uchun ruxsatingiz yoʻq.',
    ],

    'confirms' => [
        'delete_currency' => 'Ushbu valyutani oʻchirishga ishonchingiz komilmi? Bu amalni bekor qilib boʻlmaydi.',
        'set_default' => 'Ushbu valyutani asosiy qilib belgilashga ishonchingiz komilmi? Barcha kurslar qayta hisoblanadi.',
    ],
];
