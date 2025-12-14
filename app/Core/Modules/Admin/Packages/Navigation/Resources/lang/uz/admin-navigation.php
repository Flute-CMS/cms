<?php

return [
    'title' => 'Navigatsiya',
    'description' => 'Bu sahifa Flute da yaratilgan barcha navigatsiya elementlarini koʻrsatadi',
    'table' => [
        'title' => 'Sarlavha',
        'actions' => 'Amallar',
    ],
    'buttons' => [
        'create' => 'Element Yaratish',
        'edit' => 'Tahrirlash',
        'delete' => 'Oʻchirish',
    ],
    'modal' => [
        'item' => [
            'create_title' => 'Navigatsiya Elementi Yaratish',
            'edit_title' => 'Navigatsiya Elementini Tahrirlash',
            'fields' => [
                'title' => [
                    'label' => 'Sarlavha',
                    'placeholder' => 'Element sarlavasini kiriting',
                    'help' => 'Navigatsiya elementi sarlavhasi',
                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'URL kiriting (masalan, /home)',
                    'help' => 'Havola manzili. Agar elementda bolalar boʻlsa, boʻsh qoldiring.',
                ],
                'new_tab' => [
                    'label' => 'Yangi oynada ochish',
                    'help' => 'Faqat URL belgilangan boʻlsa ishlaydi',
                ],
                'icon' => [
                    'label' => 'Ikonka',
                    'placeholder' => 'Ikonka kiriting (masalan, ph.regular.house)',
                ],
                'visibility_auth' => [
                    'label' => 'Koʻrinish',
                    'help' => 'Bu navigatsiya elementini kim koʻra oladi',
                    'options' => [
                        'all' => 'Hammasi',
                        'guests' => 'Faqat mehmonlar',
                        'logged_in' => 'Faqat tizimga kirganlar',
                    ],
                ],
                'visibility' => [
                    'label' => 'Koʻrsatish Turi',
                    'help' => 'Bu element qayerda koʻrsatiladi',
                    'options' => [
                        'all' => 'Hammasi',
                        'desktop' => 'Faqat desktop',
                        'mobile' => 'Faqat mobil',
                    ],
                ],
            ],
            'roles' => [
                'title' => 'Rollar',
                'help' => 'Qaysi rollar bu elementni koʻra oladi. Agar hech biri tanlanmasa, barcha foydalanuvchilarga koʻrinadi',
            ],
        ],
    ],
    'confirms' => [
        'delete_item' => 'Ushbu navigatsiya elementini oʻchirishga ishonchingiz komilmi?',
    ],
    'messages' => [
        'invalid_sort_data' => 'Notoʻgʻri saralash maʻlumotlari.',
        'item_created' => 'Navigatsiya elementi muvaffaqiyatli yaratildi.',
        'item_updated' => 'Navigatsiya elementi muvaffaqiyatli yangilandi.',
        'item_deleted' => 'Navigatsiya elementi muvaffaqiyatli oʻchirildi.',
        'item_not_found' => 'Navigatsiya elementi topilmadi.',
    ],
];
