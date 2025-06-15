<?php

return [
    'title' => [
        'themes'      => 'Mavzular',
        'description' => 'Bu sahifada mavzularni va ularning sozlamalarini boshqarishingiz mumkin',
        'edit'        => 'Mavzuni Tahrirlash: :name',
        'create'      => 'Mavzu Qoʻshish',
    ],
    'table' => [
        'name'    => 'Nomi',
        'version' => 'Versiya',
        'status'  => 'Holat',
        'actions' => 'Amallar',
    ],
    'fields' => [
        'name' => [
            'label'       => 'Nomi',
            'placeholder' => 'Mavzu nomini kiriting',
        ],
        'version' => [
            'label'       => 'Versiya',
            'placeholder' => 'Mavzu versiyasini kiriting',
        ],
        'enabled' => [
            'label' => 'Faol',
            'help'  => 'Ushbu mavzuni yoqish yoki oʻchirish',
        ],
        'description' => [
            'label'       => 'Tavsif',
            'placeholder' => 'Mavzu tavsifini kiriting',
        ],
        'author' => [
            'label'       => 'Muallif',
            'placeholder' => 'Mavzu muallifini kiriting',
        ],
    ],
    'buttons' => [
        'save'    => 'Saqlash',
        'edit'    => 'Tahrirlash',
        'delete'  => 'Oʻchirish',
        'enable'  => 'Yoqish',
        'disable' => 'Oʻchirish',
        'refresh' => 'Mavzular Roʻyxatini Yangilash',
        'details' => 'Tafsilotlar',
        'install' => 'Oʻrnatish',
    ],
    'status' => [
        'active'       => 'Faol',
        'inactive'     => 'Nofaol',
        'not_installed'=> 'Oʻrnatilmagan',
    ],
    'confirms' => [
        'delete' => 'Ushbu mavzuni oʻchirishga ishonchingiz komilmi?',
        'install'=> 'Ushbu mavzuni oʻrnatishga ishonchingiz komilmi?',
    ],
    'messages' => [
        'save_success'   => 'Mavzu muvaffaqiyatli saqlandi.',
        'save_error'     => 'Mavzuni saqlashda xatolik: :message',
        'delete_success' => 'Mavzu muvaffaqiyatli oʻchirildi.',
        'delete_error'   => 'Mavzuni oʻchirishda xatolik: :message',
        'toggle_success' => 'Mavzu holati muvaffaqiyatli oʻzgartirildi.',
        'toggle_error'   => 'Mavzu holatini oʻzgartirishda xatolik.',
        'not_found'      => 'Mavzu topilmadi.',
        'refresh_success'=> 'Mavzular roʻyxati muvaffaqiyatli yangilandi.',
        'install_success'=> 'Mavzu muvaffaqiyatli oʻrnatildi.',
        'install_error'  => 'Mavzuni oʻrnatishda xatolik: :message',
        'enable_success' => 'Mavzu muvaffaqiyatli yoqildi.',
        'enable_error'   => 'Mavzuni yoqishda xatolik: :message',
        'disable_success'=> 'Mavzu muvaffaqiyatli oʻchirildi.',
        'disable_error'  => 'Mavzuni oʻchirishda xatolik: :message',
    ],
];
