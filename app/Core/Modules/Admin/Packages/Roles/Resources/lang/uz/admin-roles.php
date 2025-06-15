<?php

return [
    'title' => [
        'roles'             => 'Rollar',
        'roles_description' => 'Foydalanuvchi rollarini boshqarish. Eng yuqori rol eng yuqori ustuvorlikka ega.',
    ],
    'breadcrumbs' => [
        'roles' => 'Rollar',
    ],
    'buttons' => [
        'create' => 'Rol Yaratish',
        'edit'   => 'Tahrirlash',
        'delete' => 'Oʻchirish',
        'save'   => 'Saqlash',
        'update' => 'Yangilash',
    ],
    'table' => [
        'role_name' => 'Rol Nomi',
        'actions'   => 'Amallar',
    ],
    'modal' => [
        'create' => [
            'title'  => 'Rol Yaratish',
            'submit' => 'Yaratish',
        ],
        'edit' => [
            'title'  => 'Rolni Tahrirlash',
            'submit' => 'Yangilash',
        ],
        'delete' => [
            'title'   => 'Rolni Oʻchirish',
            'confirm' => 'Ushbu rolni oʻchirishga ishonchingiz komilmi?',
        ],
    ],
    'fields' => [
        'name' => [
            'label'       => 'Rol Nomi',
            'placeholder' => 'Rol nomini kiriting',
            'help'        => 'Rol uchun noyob nom',
        ],
        'color' => [
            'label' => 'Rang',
            'help'  => 'Rol bilan bogʻlangan rang',
        ],
        'permissions' => [
            'label' => 'Ruxsatnomalar',
            'help'  => 'Ushbu rol uchun ruxsatnomalarni tanlang',
        ],
        'icon' => [
            'label'       => 'Ikonka',
            'placeholder' => 'ph.regular... yoki <svg...',
            'help'        => 'Rol bilan bogʻlangan ikonka',
        ],
    ],
    'messages' => [
        'created'        => 'Rol muvaffaqiyatli yaratildi.',
        'updated'        => 'Rol muvaffaqiyatli yangilandi.',
        'deleted'        => 'Rol muvaffaqiyatli oʻchirildi.',
        'not_found'      => 'Rol topilmadi yoki uni tahrirlash uchun ruxsatingiz yoʻq.',
        'invalid_sort'   => 'Notoʻgʻri saralash maʻlumotlari.',
        'no_permissions' => 'Iltimos, kamida bitta ruxsatnoma tanlang.',
    ],
];
