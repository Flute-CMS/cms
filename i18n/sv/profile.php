<?php

return [
    "edit" => [
        "title" => "Redigera profil",

        "main" => [
            "title"       => "Huvudsakliga inställningar",
            "description" => "Här kan du ändra huvudinställningarna för ditt konto.",
            "info_title"  => "Allmän information",
            "info_description" => "Vissa data kan vara synliga för andra användare.",

            "fields" => [
                "name"                  => "Namn",
                "email"                 => "E-post",
                "password"              => "Lösenord",
                "email_verified"        => "E-post verifierad",
                "email_not_verified"    => "E-postadressen är inte verifierad",
                "password_not_set"      => "Inte anget",
                "password_not_provided" => "Inte angivet",
                "last_changed"          => "Senast ändrad",
            ],

            "password_description" => "Ett starkt lösenord hjälper till att skydda ditt konto.",

            "basic_information" => [
                "title"       => "Allmän information",
                "description" => "Ändra grundinformationen för din profil.",

                "fields" => [
                    "name"                 => "Namn",
                    "name_placeholder"     => "Fyll i ditt fullständiga namn",
                    "name_info"            => "Detta namn kommer att vara synligt för alla användare på webbplatsen",

                    "login"                => "Användarnamn",
                    "login_placeholder"    => "Ange ditt användarnamn",
                    "login_info"           => "Ditt användarnamn är endast synligt för dig och används för att logga in",

                    "uri"                  => "Profil-URL",
                    "uri_placeholder"      => "Ange din URL",
                    "uri_info"             => "Ange slug för din profil-URL. Till exempel: :example",

                    "email"                => "E-post",
                    "email_placeholder"    => "Ange din e-postadress",
                ],

                "save_changes"         => "Spara ändringar",
                "save_changes_success" => "Grundläggande information har uppdaterats.",
            ],

            "profile_images" => [
                "title"       => "Profilbilder",
                "description" => "Ladda upp din avatar och banner för att anpassa din profil.",

                "fields" => [
                    "avatar" => "Profilbild",
                    "banner" => "Annons",
                ],

                "save_changes"         => "Spara bilder",
                "save_changes_success" => "Profilbilder har uppdaterats.",
            ],

            "change_password" => [
                "title"       => "Byt lösenord",
                "description" => "Ändra ditt nuvarande lösenord för ökad säkerhet.",

                "fields" => [
                    "current_password"                => "Nuvarande lösenord",
                    "current_password_placeholder"    => "Ange nuvarande lösenord",

                    "new_password"                    => "Nytt lösenord",
                    "new_password_placeholder"        => "Ange nytt lösenord",

                    "confirm_new_password"            => "Bekräfta nytt lösenord",
                    "confirm_new_password_placeholder"=> "Upprepa det nya lösenordet",
                ],

                "save_changes"         => "Ändra lösenord",
                "save_changes_success" => "Lösenordet har ändrats.",
                "current_password_incorrect" => "Nuvarande lösenord är inkorrekt.",
                "passwords_do_not_match"      => "Lösenorden matchar inte.",
            ],

            "delete_account" => [
                "title"       => "Radera konto",
                "description" => "Borttagning av ditt konto kommer att resultera i permanent förlust av alla dina data.",
                "confirm_message" => "Är du säker på att du vill ta bort ditt konto? Alla dina data kommer att tas bort permanent.",

                "fields" => [
                    "confirmation"             => "Bekräfta borttagning",
                    "confirmation_placeholder" => "Ange ditt användarnamn för att bekräfta",
                ],

                "delete_button"       => "Radera konto",
                "delete_success"      => "Ditt konto har tagits bort.",
                "delete_failed"       => "Felaktig bekräftelse. Kontot togs inte bort.",
                "confirmation_error"  => "Vänligen ange ditt användarnamn korrekt.",
            ],

            "profile_privacy" => [
                "title"       => "Profilsynlighet",
                "description" => "Konfigurera din profil sekretessinställningar.",

                "fields" => [
                    "hidden"  => [
                        "label" => "Offentlig",
                        "info"  => "Din profil är synlig för alla användare.",
                    ],
                    "visible" => [
                        "label" => "Privat",
                        "info"  => "Din profil är dold för andra användare.",
                    ],
                ],

                "save_changes_success" => "Sekretessinställningar har uppdaterats.",
            ],

            "profile_theme" => [
                "title"       => "Systemtema",
                "description" => "Välj temat för hela systemet.",

                "fields" => [
                    "light" => [
                        "label" => "Ljust tema",
                        "info"  => "Lämplig för dagtid.",
                    ],
                    "dark"  => [
                        "label" => "Mörkt tema",
                        "info"  => "Idealisk för att arbeta på natten.",
                    ],
                ],

                "save_changes"         => "Spara tema",
                "save_changes_success" => "Profiltema har uppdaterats.",
            ],
        ],

        "settings" => [
            "title" => "Inställningar",
        ],

        "social" => [
            "title"               => "Integrationer",
            "description"         => "Anslut sociala nätverk för snabb inloggning och åtkomst till ytterligare funktioner.",
            "unlink"              => "Avlänka",
            "unlink_description"  => "Är du säker på att du vill avlänka detta sociala nätverk?",
            "default_link"        => "Förvald länk",
            "connect"             => "Länk",
            "no_socials"          => "Tyvärr finns det inga sociala nätverk i vårt system 😢",
            "show_description"    => "Visa sociala nätverk för andra användare",
            "hide_description"    => "Dölj sociala nätverk från andra användare",
            "last_social_network" => "För att koppla bort ett socialt nätverk, ange ett lösenord.",
        ],

        "payments" => [
            "title"       => "Betalningar",
            "description" => "Historia av betalningar och transaktioner.",
            "table"       => [
                "id"          => "ID",
                "date"        => "Datum",
                "gateway"     => "Betalningssätt",
                "amount"      => "Belopp",
                "status"      => "Status",
                "promo"       => "Rabattkupong",
                "transaction" => "Verifikation",
                "actions"     => "Åtgärder",
            ],
            "status" => [
                "paid"    => "Betald",
                "pending" => "Väntar",
            ],
        ],

        "upload_directory_error" => "Uppladdningsmappen finns inte.",
        "upload_failed"          => "Det gick inte att ladda upp :field.",
    ],

    "protection_warning"        => "Ange ett lösenord för att skydda ditt konto. <a href=\":link\">Sätt det</a>",
    "no_profile_modules_info"   => "Inga profilmoduler installeras i Flute. <a href=\":link\">View on marketplace</a>",
    "was_online"                => "Var online :date",
    "view"                      => "Visa profil",
    "social_deleted"            => "Socialt nätverk är inte länkat!",
    "member_since"              => "Medlem sedan :date",
    "hidden_warning"            => "Din profil är dold för andra användare.",
    "profile_hidden"            => "Denna profil är dold för andra användare.",
];
