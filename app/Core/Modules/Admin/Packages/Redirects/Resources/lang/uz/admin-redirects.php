<?php

return [
    'title' => 'Yo\'naltirishlar',
    'description' => 'Shartli URL yo\'naltirishlarni boshqarish',

    'fields' => [
        'from_url' => [
            'label' => 'Qayerdan',
            'placeholder' => '/eski-sahifa',
            'help' => 'Yo\'naltirish uchun URL manzil (masalan: /eski-sahifa)',
        ],
        'to_url' => [
            'label' => 'Qayerga',
            'placeholder' => '/yangi-sahifa',
            'help' => 'Yo\'naltirish uchun maqsad URL',
        ],
        'conditions' => [
            'label' => 'Shartlar',
            'help' => 'Yo\'naltirishni faollashtirish uchun bajarilishi kerak bo\'lgan ixtiyoriy shartlar',
        ],
        'condition_type' => [
            'label' => 'Turi',
            'placeholder' => 'Shart turini tanlang',
        ],
        'condition_operator' => [
            'label' => 'Operator',
            'placeholder' => 'Operatorni tanlang',
        ],
        'condition_value' => [
            'label' => 'Qiymat',
            'placeholder' => 'Qiymatni kiriting',
        ],
    ],

    'condition_types' => [
        'ip' => 'IP manzil',
        'cookie' => 'Cookie',
        'referer' => 'Referer',
        'request_method' => 'HTTP usul',
        'user_agent' => 'User Agent',
        'header' => 'HTTP sarlavha',
        'lang' => 'Til',
    ],

    'operators' => [
        'equals' => 'Teng',
        'not_equals' => 'Teng emas',
        'contains' => 'O\'z ichiga oladi',
        'not_contains' => 'O\'z ichiga olmaydi',
    ],

    'buttons' => [
        'add' => 'Yo\'naltirish qo\'shish',
        'save' => 'Saqlash',
        'edit' => 'Tahrirlash',
        'delete' => 'O\'chirish',
        'actions' => 'Amallar',
        'add_condition_group' => 'Shartlar guruhini qo\'shish',
        'add_condition' => 'Shart qo\'shish',
        'remove_condition' => 'O\'chirish',
        'clear_cache' => 'Keshni tozalash',
    ],

    'messages' => [
        'save_success' => 'Yo\'naltirish muvaffaqiyatli saqlandi.',
        'update_success' => 'Yo\'naltirish muvaffaqiyatli yangilandi.',
        'delete_success' => 'Yo\'naltirish muvaffaqiyatli o\'chirildi.',
        'not_found' => 'Yo\'naltirish topilmadi.',
        'cache_cleared' => 'Yo\'naltirishlar keshi muvaffaqiyatli tozalandi.',
        'route_conflict' => 'Diqqat: ":url" URL mavjud ":route" yo\'l bilan ziddiyatda. Yo\'naltirish kutilganidek ishlamasligi mumkin, chunki yo\'l ustunlikka ega.',
        'from_url_required' => '«Qayerdan» maydoni to\'ldirilishi shart.',
        'to_url_required' => '«Qayerga» maydoni to\'ldirilishi shart.',
        'same_urls' => '«Qayerdan» va «Qayerga» URL bir xil bo\'lishi mumkin emas.',
    ],

    'empty' => [
        'title' => 'Hozircha yo\'naltirishlar yo\'q',
        'sub' => 'URL yo\'naltirishlarni boshqarish uchun birinchi yo\'naltirishni yarating',
    ],

    'confirms' => [
        'delete' => 'Bu yo\'naltirishni o\'chirishga ishonchingiz komilmi? Bu amalni qaytarib bo\'lmaydi.',
    ],

    'table' => [
        'from' => 'Qayerdan',
        'to' => 'Qayerga',
        'conditions' => 'Shartlar',
        'actions' => 'Amallar',
    ],

    'modal' => [
        'create_title' => 'Yo\'naltirish yaratish',
        'edit_title' => 'Yo\'naltirishni tahrirlash',
        'conditions_title' => 'Yo\'naltirish shartlari',
        'conditions_help' => 'Shartlar guruhlari orasida YOKI mantiqiy, guruh ichida — VA mantiqiy ishlatiladi.',
        'group_label' => 'Guruh :number',
    ],

    'settings' => [
        'title' => 'Sozlamalar',
        'cache_time' => [
            'label' => 'Kesh muddati (soniyalarda)',
            'help' => 'Yo\'naltirish qoidalari qancha vaqt keshlanadi. O\'chirish uchun 0 ni belgilang.',
            'placeholder' => '3600',
        ],
    ],

    'alert' => [
        'route_conflict_title' => 'Yo\'l ziddiyati aniqlandi',
    ],
];
