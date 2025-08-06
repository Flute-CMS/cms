<?php

return [
    "edit" => [
        "title" => "Upravit profil",

        "main" => [
            "title"       => "Hlavní nastavení",
            "description" => "Zde můžete změnit hlavní nastavení svého účtu.",
            "info_title"  => "Základní informace",
            "info_description" => "Některá data mohou být viditelná ostatním uživatelům.",

            "fields" => [
                "name"              => "Jméno",
                "email"             => "E-mail",
                "password"          => "Heslo",
                "email_verified"    => "E-mail ověřen",
                "email_not_verified" => "E-mail neověřen",
                "password_not_set"  => "Nenastaveno",
                "password_not_provided" => "Nezadáno",
                "last_changed"      => "Naposledy změněno",
                "verify_email"      => "Ověřit e-mail",
            ],

            "password_description" => "Silné heslo pomáhá chránit váš účet.",

            "basic_information" => [
                "title"       => "Základní informace",
                "description" => "Změňte základní informace svého profilu.",

                "fields" => [
                    "name"              => "Jméno",
                    "name_placeholder"  => "Zadejte své celé jméno",
                    "name_info"         => "Toto jméno bude viditelné všem uživatelům na webu",

                    "login"             => "Uživatelské jméno",
                    "login_placeholder" => "Zadejte své uživatelské jméno",
                    "login_info"        => "Vaše uživatelské jméno je viditelné pouze vám a slouží k přihlášení",

                    "uri"               => "URL profilu",
                    "uri_placeholder"   => "Zadejte svou URL",
                    "uri_info"          => "Zadejte slug pro URL vašeho profilu. Například: :example",

                    "email"             => "E-mail",
                    "email_placeholder" => "Zadejte svou e-mailovou adresu",
                ],

                "save_changes"       => "Uložit změny",
                "save_changes_success" => "Základní informace úspěšně aktualizovány.",
            ],

            "profile_images" => [
                "title"       => "Obrázky profilu",
                "description" => "Nahrajte svůj avatar a banner k personalizaci svého profilu.",

                "fields" => [
                    "avatar" => "Avatar",
                    "banner" => "Banner",
                ],

                "save_changes"       => "Uložit obrázky",
                "save_changes_success" => "Obrázky profilu úspěšně aktualizovány.",
            ],

            "change_password" => [
                "title"       => "Změnit heslo",
                "description" => "Změňte své aktuální heslo pro zvýšení bezpečnosti.",

                "fields" => [
                    "current_password"              => "Aktuální heslo",
                    "current_password_placeholder"  => "Zadejte aktuální heslo",

                    "new_password"                  => "Nové heslo",
                    "new_password_placeholder"      => "Zadejte nové heslo",

                    "confirm_new_password"          => "Potvrďte nové heslo",
                    "confirm_new_password_placeholder"=> "Zopakujte nové heslo",
                ],

                "save_changes"       => "Změnit heslo",
                "save_changes_success" => "Heslo úspěšně změněno.",
                "current_password_incorrect" => "Aktuální heslo je nesprávné.",
                "passwords_do_not_match"       => "Hesla se neshodují.",
            ],

            "delete_account" => [
                "title"           => "Smazat účet",
                "description"     => "Smazáním účtu dojde k trvalé ztrátě všech vašich dat.",
                "confirm_message" => "Opravdu chcete smazat svůj účet? Všechna vaše data budou trvale odstraněna.",

                "fields" => [
                    "confirmation"             => "Potvrzení smazání",
                    "confirmation_placeholder" => "Zadejte své uživatelské jméno pro potvrzení",
                ],

                "delete_button"       => "Smazat účet",
                "delete_success"      => "Váš účet byl úspěšně smazán.",
                "delete_failed"       => "Nesprávné potvrzení. Účet nebyl smazán.",
                "confirmation_error"  => "Zadejte prosím své uživatelské jméno správně.",
            ],

            "profile_privacy" => [
                "title"       => "Soukromí profilu",
                "description" => "Nakonfigurujte nastavení soukromí svého profilu.",

                "fields" => [
                    "hidden"  => [
                        "label" => "Veřejný",
                        "info"  => "Váš profil je viditelný všem uživatelům.",
                    ],
                    "visible" => [
                        "label" => "Soukromý",
                        "info"  => "Váš profil je skrytý před ostatními uživateli.",
                    ],
                ],

                "save_changes_success" => "Nastavení soukromí úspěšně aktualizováno.",
            ],

            "profile_theme" => [
                "title"       => "Systémové téma",
                "description" => "Vyberte téma pro celý systém.",

                "fields" => [
                    "light" => [
                        "label" => "Světlé téma",
                        "info"  => "Vhodné pro denní použití.",
                    ],
                    "dark"  => [
                        "label" => "Tmavé téma",
                        "info"  => "Ideální pro práci v noci.",
                    ],
                    "system" => [
                        "label" => "Systémové téma",
                        "info"  => "Téma bude automaticky vybráno na základě nastavení vašeho zařízení.",
                    ],
                ],

                "save_changes"       => "Uložit téma",
                "save_changes_success" => "Téma profilu úspěšně aktualizováno.",
            ],
        ],

        "settings" => [
            "title" => "Nastavení",
        ],

        "social" => [
            "title"             => "Integrace",
            "description"       => "Propojte sociální sítě pro rychlé přihlášení a přístup k dalším funkcím.",
            "unlink"            => "Odpojit",
            "unlink_description" => "Opravdu chcete odpojit tuto sociální síť?",
            "default_link"      => "Výchozí odkaz",
            "connect"           => "Propojit",
            "no_socials"        => "Bohužel, v našem systému nejsou žádné sociální sítě 😢",
            "show_description"  => "Zobrazit sociální síť ostatním uživatelům",
            "hide_description"  => "Skrýt sociální síť před ostatními uživateli",
            "last_social_network" => "Pro odpojení sociální sítě si nastavte heslo.",
        ],

        "payments" => [
            "title"       => "Platby",
            "description" => "Historie plateb a transakcí.",
            "table"       => [
                "id"          => "ID",
                "date"        => "Datum",
                "gateway"     => "Platební metoda",
                "amount"      => "Částka",
                "status"      => "Stav",
                "promo"       => "Promo kód",
                "transaction" => "Transakce",
                "actions"     => "Akce",
            ],
            "status" => [
                "paid"    => "Zaplaceno",
                "pending" => "Čekající",
            ],
        ],

        "upload_directory_error" => "Adresář pro nahrávání neexistuje.",
        "upload_failed"          => "Nepodařilo se nahrát :field.",
    ],

    "protection_warning"    => "Nastavte si heslo pro ochranu vašeho účtu. <a href=\":link\">Nastavit</a>",
    "no_profile_modules_info" => "V Flute nejsou nainstalovány žádné profilové moduly. <a href=\":link\">Zobrazit na tržišti</a>",
    "was_online"            => "Byl online :date",
    "view"                  => "Zobrazit profil",
    "social_deleted"        => "Sociální síť úspěšně odpojena!",
    "member_since"          => "Členem od :date",
    "hidden_warning"        => "Váš profil je skrytý před ostatními uživateli.",
    "profile_hidden"        => "Tento profil je skrytý před ostatními uživateli.",
    "verification_warning"  => "Ověřte svou e-mailovou adresu pro přístup k dalším funkcím. <a href=\":link\">Ověřit</a>",
];