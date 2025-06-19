<?php

return [
    "edit" => [
        "title" => "Profilni Tahrirlash",

        "main" => [
            "title"       => "Asosiy Sozlamalar",
            "description" => "Bu yerda hisobingizning asosiy sozlamalarini oʻzgartirishingiz mumkin.",
            "info_title"  => "Asosiy Maʻlumotlar",
            "info_description" => "Baʻzi maʻlumotlar boshqa foydalanuvchilar uchun koʻrinadigan boʻlishi mumkin.",

            "fields" => [
                "name"                  => "Ism",
                "email"                 => "Email",
                "password"              => "Parol",
                "email_verified"        => "Email tasdiqlangan",
                "email_not_verified"    => "Email tasdiqlanmagan",
                "password_not_set"      => "Oʻrnatilmagan",
                "password_not_provided" => "Berilmagan",
                "last_changed"          => "Oxirgi oʻzgartirilgan",
                "verify_email"          => "Emailni tasdiqlash",
            ],

            "password_description" => "Kuchli parol hisobingizni himoya qilishga yordam beradi.",

            "basic_information" => [
                "title"       => "Asosiy Maʻlumotlar",
                "description" => "Profilingizning asosiy maʻlumotlarini oʻzgartiring.",

                "fields" => [
                    "name"                 => "Ism",
                    "name_placeholder"     => "Toʻliq ismingizni kiriting",
                    "name_info"            => "Bu ism saytdagi barcha foydalanuvchilar uchun koʻrinadi",

                    "login"                => "Foydalanuvchi nomi",
                    "login_placeholder"    => "Foydalanuvchi nomingizni kiriting",
                    "login_info"           => "Foydalanuvchi nomingiz faqat siz uchun koʻrinadi va tizimga kirish uchun ishlatiladi",

                    "uri"                  => "Profil URL",
                    "uri_placeholder"      => "URL manzilni kiriting",
                    "uri_info"             => "Profil URL manzili uchun slug kiriting. Masalan: :example",

                    "email"                => "Email",
                    "email_placeholder"    => "Email manzilingizni kiriting",
                ],

                "save_changes"         => "Oʻzgarishlarni saqlash",
                "save_changes_success" => "Asosiy maʻlumotlar muvaffaqiyatli yangilandi.",
            ],

            "profile_images" => [
                "title"       => "Profil Rasmlari",
                "description" => "Profilingizni shaxsiylashtirish uchun avatar va banner yuklang.",

                "fields" => [
                    "avatar" => "Avatar",
                    "banner" => "Banner",
                ],

                "save_changes"         => "Rasmlarni saqlash",
                "save_changes_success" => "Profil rasmlari muvaffaqiyatli yangilandi.",
            ],

            "change_password" => [
                "title"       => "Parolni Oʻzgartirish",
                "description" => "Xavfsizlikni oshirish uchun joriy parolingizni oʻzgartiring.",

                "fields" => [
                    "current_password"                => "Joriy parol",
                    "current_password_placeholder"    => "Joriy parolni kiriting",

                    "new_password"                    => "Yangi parol",
                    "new_password_placeholder"        => "Yangi parolni kiriting",

                    "confirm_new_password"            => "Yangi parolni tasdiqlash",
                    "confirm_new_password_placeholder"=> "Yangi parolni takrorlang",
                ],

                "save_changes"         => "Parolni oʻzgartirish",
                "save_changes_success" => "Parol muvaffaqiyatli oʻzgartirildi.",
                "current_password_incorrect" => "Joriy parol notoʻgʻri.",
                "passwords_do_not_match"      => "Parollar mos kelmaydi.",
            ],

            "delete_account" => [
                "title"       => "Hisobni Oʻchirish",
                "description" => "Hisobingizni oʻchirish barcha maʻlumotlaringizning doimiy yoʻqolishiga olib keladi.",
                "confirm_message" => "Haqiqatan ham hisobingizni oʻchirmoqchimisiz? Barcha maʻlumotlaringiz doimiy ravishda olib tashlanadi.",

                "fields" => [
                    "confirmation"             => "Oʻchirishni tasdiqlash",
                    "confirmation_placeholder" => "Tasdiqlash uchun foydalanuvchi nomingizni kiriting",
                ],

                "delete_button"       => "Hisobni Oʻchirish",
                "delete_success"      => "Hisobingiz muvaffaqiyatli oʻchirildi.",
                "delete_failed"       => "Notoʻgʻri tasdiqlash. Hisob oʻchirilmadi.",
                "confirmation_error"  => "Iltimos, foydalanuvchi nomingizni toʻgʻri kiriting.",
            ],

            "profile_privacy" => [
                "title"       => "Profil Maxfiyligi",
                "description" => "Profil maxfiylik sozlamalaringizni konfiguratsiya qiling.",

                "fields" => [
                    "hidden"  => [
                        "label" => "Ochiq",
                        "info"  => "Profilingiz barcha foydalanuvchilar uchun koʻrinadi.",
                    ],
                    "visible" => [
                        "label" => "Yopiq",
                        "info"  => "Profilingiz boshqa foydalanuvchilardan yashirilgan.",
                    ],
                ],

                "save_changes_success" => "Maxfiylik sozlamalari muvaffaqiyatli yangilandi.",
            ],

            "profile_theme" => [
                "title"       => "Tizim Mavzusi",
                "description" => "Butun tizim uchun mavzuni tanlang.",

                "fields" => [
                    "light" => [
                        "label" => "Yorugʻ mavzu",
                        "info"  => "Kunduzgi vaqt uchun mos.",
                    ],
                    "dark"  => [
                        "label" => "Qorongʻu mavzu",
                        "info"  => "Kechki ishlash uchun ideal.",
                    ],
                    "system" => [
                        "label" => "Tizim mavzusi",
                        "info"  => "Mavzu qurilmangizga qarab avtomatik tanlanadi.",
                    ],
                ],

                "save_changes"         => "Mavzuni saqlash",
                "save_changes_success" => "Profil mavzusi muvaffaqiyatli yangilandi.",
            ],
        ],

        "settings" => [
            "title" => "Sozlamalar",
        ],

        "social" => [
            "title"               => "Integratsiyalar",
            "description"         => "Tez kirish va qoʻshimcha funksiyalardan foydalanish uchun ijtimoiy tarmoqlarni ulang.",
            "unlink"              => "Uzish",
            "unlink_description"  => "Haqiqatan ham ushbu ijtimoiy tarmoqni uzmoqchimisiz?",
            "default_link"        => "Standart havola",
            "connect"             => "Ulash",
            "no_socials"          => "Afsuski, bizning tizimimizdagi ijtimoiy tarmoqlar yoʻq 😢",
            "show_description"    => "Ijtimoiy tarmoqni boshqa foydalanuvchilarga koʻrsatish",
            "hide_description"    => "Ijtimoiy tarmoqni boshqa foydalanuvchilardan yashirish",
            "last_social_network" => "Ijtimoiy tarmoqni uzish uchun parol oʻrnating.",
        ],

        "payments" => [
            "title"       => "Toʻlovlar",
            "description" => "Toʻlovlar va tranzaksiyalar tarixi.",
            "table"       => [
                "id"          => "ID",
                "date"        => "Sana",
                "gateway"     => "Toʻlov usuli",
                "amount"      => "Miqdor",
                "status"      => "Holat",
                "promo"       => "Promo kod",
                "transaction" => "Tranzaksiya",
                "actions"     => "Amallar",
            ],
            "status" => [
                "paid"    => "Toʻlangan",
                "pending" => "Kutilmoqda",
            ],
        ],

        "upload_directory_error" => "Yuklash jildi mavjud emas.",
        "upload_failed"          => ":field ni yuklash muvaffaqiyatsiz.",
    ],

    "protection_warning"        => "Hisobingizni himoya qilish uchun parol oʻrnating. <a href=\":link\">Oʻrnatish</a>",
    "no_profile_modules_info"   => "Flute da profil modullari oʻrnatilmagan. <a href=\":link\">Bozorda koʻrish</a>",
    "was_online"                => ":date da onlayn edi",
    "view"                      => "Profilni koʻrish",
    "social_deleted"            => "Ijtimoiy tarmoq muvaffaqiyatli uzildi!",
    "member_since"              => ":date dan beri aʻzo",
    "hidden_warning"            => "Profilingiz boshqa foydalanuvchilardan yashirilgan.",
    "profile_hidden"            => "Bu profil boshqa foydalanuvchilardan yashirilgan.",
    "verification_warning"      => "Qoʻshimcha funksiyalardan foydalanish uchun email manzilingizni tasdiqlang. <a href=\":link\">Tasdiqlash</a>",
];
