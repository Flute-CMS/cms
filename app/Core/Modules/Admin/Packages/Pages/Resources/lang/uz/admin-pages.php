<?php

return [
    'search_pages' => 'Sahifalar Qidirish',
    'title' => [
        'list'           => 'Sahifalar',
        'edit'           => 'Sahifani Tahrirlash',
        'create'         => 'Sahifa Qoʻshish',
        'description'    => 'Fluteʻda yaratilgan barcha sahifalar bu yerda koʻrsatilgan',
        'main_info'      => 'Asosiy Maʻlumotlar',
        'actions'        => 'Amallar',
        'actions_description' => 'Sahifa ustidagi amallar',
        'content'        => 'Kontent',
        'blocks'         => 'Sahifa Bloklari',
        'seo'            => 'SEO Sozlamalari',
        'permissions'    => 'Ruxsatnomalar',
    ],

    'tabs' => [
        'main'           => 'Asosiy',
        'blocks'         => 'Bloklar',
        'permissions'    => 'Ruxsatnomalar',
    ],

    'fields' => [
        'route' => [
            'label'       => 'Marshrut',
            'placeholder' => 'Sahifa marshrutini kiriting (masalan, /about)',
            'help'        => 'Ushbu sahifa uchun URL yoʻli',
        ],
        'title' => [
            'label'       => 'Sarlavha',
            'placeholder' => 'Sahifa sarlavasini kiriting',
            'help'        => 'Brauzer va qidiruv tizimlarida koʻrsatiladigan sahifa sarlavhasi',
        ],
        'description' => [
            'label'       => 'Tavsif',
            'placeholder' => 'Sahifa tavsifini kiriting',
            'help'        => 'Qidiruv tizimlari uchun meta tavsif',
        ],
        'keywords' => [
            'label'       => 'Kalit Soʻzlar',
            'placeholder' => 'Kalit soʻzlarni vergul bilan ajratib kiriting',
            'help'        => 'Qidiruv tizimlari uchun meta kalit soʻzlar',
        ],
        'robots' => [
            'label'       => 'Robots',
            'placeholder' => 'index, follow',
            'help'        => 'Qidiruv tizimi robotlari uchun koʻrsatmalar',
        ],
        'og_image' => [
            'label'       => 'OG Rasm',
            'placeholder' => 'Rasm URL ini kiriting',
            'help'        => 'Ijtimoiy tarmoqlarda ulashish uchun rasm',
        ],
        'created_at' => 'Yaratilgan Sana',
    ],

    'blocks' => [
        'title' => 'Sahifa Bloklari',
        'fields' => [
            'widget' => [
                'label'       => 'Vidjet',
                'placeholder' => 'Vidjet tanlash',
                'help'        => 'Ushbu blok uchun vidjet turi',
            ],
            'gridstack' => [
                'label'       => 'Grid Sozlamalari',
                'placeholder' => 'Grid sozlamalarini JSON formatida kiriting',
                'help'        => 'GridStack joylashuv sozlamalari',
            ],
            'settings' => [
                'label'       => 'Blok Sozlamalari',
                'placeholder' => 'Blok sozlamalarini JSON formatida kiriting',
                'help'        => 'Vidjet uchun maxsus sozlamalar',
            ],
        ],
        'add' => [
            'title' => 'Blok Qoʻshish',
            'button' => 'Blok Qoʻshish',
        ],
        'edit' => [
            'title' => 'Blokni Tahrirlash',
        ],
        'delete' => [
            'confirm' => 'Ushbu blokni oʻchirishga ishonchingiz komilmi?',
        ],
    ],

    'buttons' => [
        'add'    => 'Qoʻshish',
        'save'   => 'Saqlash',
        'cancel' => 'Bekor Qilish',
        'delete' => 'Oʻchirish',
        'edit'   => 'Tahrirlash',
        'actions' => 'Amallar',
        'goto'   => 'Oʻtish',
    ],

    'messages' => [
        'page_not_found'             => 'Sahifa topilmadi.',
        'block_not_found'            => 'Blok topilmadi.',
        'save_success'               => 'Sahifa muvaffaqiyatli saqlandi.',
        'delete_success'             => 'Sahifa muvaffaqiyatli oʻchirildi.',
        'block_add_success'          => 'Blok muvaffaqiyatli qoʻshildi.',
        'block_update_success'       => 'Blok muvaffaqiyatli yangilandi.',
        'block_delete_success'       => 'Blok muvaffaqiyatli oʻchirildi.',
        'save_page_first'            => 'Iltimos, avval sahifani saqlang.',
        'invalid_json'               => 'Notoʻgʻri JSON formati.',
        'page_deleted'               => 'Sahifa muvaffaqiyatli oʻchirib tashlandi.',
        'page_updated'               => 'Sahifa muvaffaqiyatli yangilandi.',
        'page_created'               => 'Sahifa muvaffaqiyatli yaratildi.',
        'route_exists'               => 'Bunday marshrutga ega sahifa allaqachon mavjud.',
        'invalid_route'              => 'Marshrut / bilan boshlanishi va faqat toʻgʻri URL belgilarini oʻz ichiga olishi kerak.',
        'no_permission.manage'       => 'Sahifalarni boshqarish uchun ruxsatingiz yoʻq.',
        'no_permission.delete'       => 'Sahifalarni oʻchirish uchun ruxsatingiz yoʻq.',
    ],

    'confirms' => [
        'delete_page' => 'Ushbu sahifani oʻchirishga ishonchingiz komilmi? Bu amalni bekor qilib boʻlmaydi.',
    ],
];
