<?php

return [
    'title' => [
        'list'        => 'API Kalitlari',
        'description' => 'Tashqi kirish uchun API kalitlarini boshqarish',
        'create'      => 'API Kalit Yaratish',
        'edit'        => 'API Kalitni Tahrirlash',
    ],
    'fields' => [
        'key' => [
            'label'       => 'API Kalit',
            'placeholder' => 'API kalitni kiriting',
            'help'        => 'Bu kalit API autentifikatsiya uchun ishlatiladi',
        ],
        'name' => [
            'label'       => 'Nom',
            'placeholder' => 'Kalit nomini kiriting',
            'help'        => 'Bu nomdan kalitni aniqlash uchun foydalanishingiz mumkin',
        ],
        'permissions' => [
            'label' => 'Ruxsatnomalar',
        ],
        'created_at'   => 'Yaratilgan Sana',
        'last_used_at' => 'Oxirgi Foydalanilgan Sana',
        'never'        => 'Hech qachon',
    ],
    'buttons' => [
        'actions' => 'Amallar',
        'add'     => 'Kalit Qoʻshish',
        'save'    => 'Saqlash',
        'edit'    => 'Tahrirlash',
        'delete'  => 'Oʻchirish',
    ],
    'confirms' => [
        'delete_key' => 'Ushbu API kalitni oʻchirishga ishonchingiz komilmi?',
    ],
    'messages' => [
        'save_success'    => 'API kalit muvaffaqiyatli saqlandi.',
        'key_not_found'   => 'API kalit topilmadi.',
        'no_permissions'  => 'Iltimos, kamida bitta ruxsatnoma tanlang.',
        'update_success'  => 'API kalit muvaffaqiyatli yangilandi.',
        'update_error'    => 'API kalitni yangilashda xatolik: :message',
        'delete_success'  => 'API kalit muvaffaqiyatli oʻchirildi.',
        'delete_error'    => 'API kalitni oʻchirishda xatolik: :message',
    ],
];
