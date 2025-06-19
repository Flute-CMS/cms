<?php

return [
    'title'       => 'Footer',
    'description' => 'Footer elementlari va ijtimoiy havolalarni boshqarish',
    'tabs' => [
        'main_elements' => 'Asosiy Elementlar',
        'social'        => 'Ijtimoiy Tarmoqlar',
    ],
    'table' => [
        'title'   => 'Sarlavha',
        'icon'    => 'Ikonka',
        'url'     => 'URL',
        'actions' => 'Amallar',
    ],
    'sections' => [
        'main_links' => [
            'title'       => 'Asosiy Havolalar',
            'description' => 'Bu sahifa Flute da yaratilgan barcha footer elementlarini koʻrsatadi',
        ],
        'social_links' => [
            'title'       => 'Footer Ijtimoiy Havolalar',
            'description' => 'Bu sahifa sayt footerida koʻrsatiladigan barcha ijtimoiy tarmoqlarni koʻrsatadi',
        ],
    ],
    'buttons' => [
        'create' => 'Yaratish',
        'edit'   => 'Tahrirlash',
        'delete' => 'Oʻchirish',
    ],
    'modal' => [
        'footer_item' => [
            'create_title' => 'Footer Elementi Yaratish',
            'edit_title'   => 'Footer Elementini Tahrirlash',
            'fields' => [
                'title' => [
                    'label'       => 'Sarlavha',
                    'placeholder' => 'Element sarlavasini kiriting',
                    'help'        => 'Footer elementi sarlavhasi',
                ],
                'url' => [
                    'label'       => 'URL',
                    'placeholder' => 'URL kiriting (masalan, /contact)',
                    'help'        => 'Havola manzili. Agar elementda bolalar boʻlsa, boʻsh qoldiring.',
                ],
                'new_tab' => [
                    'label' => 'Yangi oynada ochish',
                    'help'  => 'Faqat URL belgilangan boʻlsa ishlaydi',
                ],
            ],
        ],
        'social' => [
            'create_title' => 'Ijtimoiy Tarmoq Yaratish',
            'edit_title'   => 'Ijtimoiy Tarmoqni Tahrirlash',
            'fields' => [
                'name' => [
                    'label'       => 'Nomi',
                    'placeholder' => 'Ijtimoiy tarmoq nomini kiriting',
                    'help'        => 'Ijtimoiy tarmoq nomi (masalan, Discord)',
                ],
                'icon' => [
                    'label'       => 'Ikonka',
                    'placeholder' => 'Ikonka kiriting (masalan, ph.regular.discord-logo)',
                    'help'        => 'Ikonka identifikatori, masalan "ph.bold.discord-logo-bold"',
                ],
                'url' => [
                    'label'       => 'URL',
                    'placeholder' => 'URL kiriting (masalan, https://discord.gg/yourpage)',
                    'help'        => 'Ijtimoiy tarmoq sahifangizga havola',
                ],
            ],
        ],
    ],
    'confirms' => [
        'delete_item'   => 'Ushbu footer elementini oʻchirishga ishonchingiz komilmi?',
        'delete_social' => 'Ushbu ijtimoiy tarmoqni oʻchirishga ishonchingiz komilmi?',
    ],
    'messages' => [
        'invalid_sort_data' => 'Notoʻgʻri saralash maʻlumotlari.',
        'item_created'      => 'Footer elementi muvaffaqiyatli yaratildi.',
        'item_updated'      => 'Footer elementi muvaffaqiyatli yangilandi.',
        'item_deleted'      => 'Footer elementi muvaffaqiyatli oʻchirildi.',
        'item_not_found'    => 'Footer elementi topilmadi.',
        'social_created'    => 'Ijtimoiy tarmoq muvaffaqiyatli yaratildi.',
        'social_updated'    => 'Ijtimoiy tarmoq muvaffaqiyatli yangilandi.',
        'social_deleted'    => 'Ijtimoiy tarmoq muvaffaqiyatli oʻchirildi.',
        'social_not_found'  => 'Ijtimoiy tarmoq topilmadi.',
    ],
];
