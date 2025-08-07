<?php

return [
    'title' => [
        'social' => 'Ijtimoiy Tarmoqlar',
        'description' => 'Bu sahifada autentifikatsiya uchun ijtimoiy tarmoqlarni sozlashingiz mumkin',
        'edit' => 'Ijtimoiy Tarmoqni Tahrirlash: :name',
        'create' => 'Ijtimoiy Tarmoq Qoʻshish',
    ],
    'table' => [
        'social' => 'Ijtimoiy Tarmoq',
        'cooldown' => 'Kutish Vaqti',
        'registration' => 'Roʻyxatdan Oʻtish',
        'status' => 'Holat',
        'actions' => 'Amallar',
    ],
    'fields' => [
        'icon' => [
            'label' => 'Ikonka',
            'placeholder' => 'masalan: ph.regular.steam',
        ],
        'allow_register' => [
            'label' => 'Roʻyxatdan Oʻtishga Ruxsat Berish',
            'help' => 'Ushbu ijtimoiy tarmoq orqali roʻyxatdan oʻtish mumkinmi',
        ],
        'cooldown_time' => [
            'label' => 'Kutish Vaqti',
            'help' => 'Misol: 3600 (soniya, 1 soatga teng)',
            'small' => 'Misol: 3600 soniya (1 soat)',
            'placeholder' => '3600 soniya',
            'popover' => 'Ijtimoiy bogʻlanishni oʻchirish va uni qayta qoʻshish oʻrtasidagi vaqt',
        ],
        'redirect_uri' => [
            'first' => 'Birinchi URI',
            'second' => 'Ikkinchi URI',
        ],
        'driver' => [
            'label' => 'Auth Drayver',
            'placeholder' => 'Drayverni tanlang',
        ],
        'client_id' => [
            'label' => 'Client ID',
        ],
        'client_secret' => [
            'label' => 'Client Secret',
        ],
    ],
    'buttons' => [
        'add' => 'Qoʻshish',
        'save' => 'Saqlash',
        'edit' => 'Tahrirlash',
        'delete' => 'Oʻchirish',
        'enable' => 'Yoqish',
        'disable' => 'Oʻchirish',
    ],
    'status' => [
        'active' => 'Faol',
        'inactive' => 'Nofaol',
    ],
    'confirms' => [
        'delete' => 'Ushbu ijtimoiy tarmoqni oʻchirishga ishonchingiz komilmi?',
    ],
    'messages' => [
        'save_success' => 'Ijtimoiy tarmoq muvaffaqiyatli saqlandi.',
        'save_error' => 'Saqlashda xatolik: :message',
        'delete_success' => 'Ijtimoiy tarmoq muvaffaqiyatli oʻchirildi.',
        'delete_error' => 'Oʻchirishda xatolik: :message',
        'toggle_success' => 'Ijtimoiy tarmoq holati muvaffaqiyatli oʻzgartirildi.',
        'toggle_error' => 'Holatni oʻzgartirishda xatolik.',
        'not_found' => 'Ijtimoiy tarmoq topilmadi.',
    ],
    'edit' => [
        'default' => ':driver drayveri sinovdan oʻtmagan. U toʻgʻri ishlamasligi mumkin. Parametrlarni qoʻlda sozlashingiz kerak.',
        'discord' => 'Discord sozlash uchun <a href="https://docs.flute-cms.com/social-auth/discord" target="_blank">hujjatlarni</a> koʻring.',
        'discord_token' => 'Bot Token',
        'discord_token_help' => '<a class="accent" href="#">Discord bilan rol sinxronizatsiyasi</a> uchun kerak. Ixtiyoriy.',
        'steam_success' => 'Hammasi yaxshi, sozlash shart emas.',
        'steam_error' => 'STEAM API kaliti oʻrnatilmagan. Iltimos, uni <a href="/admin/main-settings" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">sozlamalarda</a> konfigurasiya qiling.',
        'telegram' => 'Telegram sozlash uchun <a href="https://docs.flute-cms.com/social-auth/telegram" target="_blank">hujjatlarni</a> koʻring.',
        'telegram_token' => 'Bot Token',
        'telegram_token_placeholder' => '1234546',
        'telegram_bot_name' => 'Bot Nomi',
        'telegram_bot_name_placeholder' => 'masalan: MyAwesomeBot',
    ],
    'no_drivers' => 'Drayverlar mavjud emas.',
];
