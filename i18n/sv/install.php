<?php

return [
    "title" => "Installation av Flute CMS",

    "welcome" => [
        "title"       => "Välkommen till Flute CMS",
        "get_started" => "Kom igång",
    ],

    "requirements" => [
        "title"           => "Systemkrav",
        "description"     => "Se till att alla krav uppfylls innan installationen påbörjas.",
        "php"             => "PHP",
        "extensions"      => "Tillägg",
        "directories"     => "Kataloger",
        "continue"        => "Fortsätt",
        "writable"        => "Katalogen är skrivbar",
        "writable_error"  => "Katalogen är inte skrivbar",
        "fix_errors"      => "Vänligen åtgärda alla fel innan du fortsätter",
    ],

    "common" => [
        "next"           => "Nästa steg",
        "back"           => "Föregående steg",
        "finish"         => "Avsluta installationen",
        "finish_success" => "Installationen slutförd framgångsrikt.",
    ],

    "flute_key" => [
        "title"          => "Licensnyckel",
        "description"    => "Ange licensnyckeln för Flute CMS för att fortsätta installationen.",
        "placeholder"    => "Ange licensnyckel",
        "hint"           => "Standardnyckel för testning: Flute@Installer",
        "error_empty"    => "Licensnyckel krävs",
        "error_invalid"  => "Den angivna licensnyckeln är ogiltig",
        "label"          => "Licensnyckel (valfritt)",
    ],

    "database" => [
        "heading"                   => "Databas konfiguration",
        "subheading"                => "Ange databasanslutningsparametrar för Flute CMS installation",
        "driver"                    => "Databastyp",
        "host"                      => "Värddator",
        "port"                      => "Port",
        "database"                  => "Databasnamn",
        "username"                  => "Användarnamn",
        "password"                  => "Lösenord",
        "prefix"                    => "Tabell prefix",
        "sqlite_note"               => "För SQLite anger du bara filnamnet. Filen kommer att skapas i lagring/databas/.",
        "test_connection"           => "Testa anslutning",
        "connection_success"        => "Databasanslutning har upprättats",
        "error_host_required"       => "Värd krävs",
        "error_database_required"   => "Databasnamn måste anges",
        "error_sqlite_dir"          => "Det gick inte att skapa katalog för SQLite",
        "error_driver_not_supported"=> "Den valda databasdrivrutinen stöds inte",
    ],

    "admin_user" => [
        "heading"              => "Skapa administratör",
        "subheading"           => "Skapa ett administratörskonto för att hantera flöjt CMS",
        "name"                 => "Fullständigt namn",
        "email"                => "E-post",
        "login"                => "Användarnamn",
        "login_help"           => "Används för inloggning, måste vara unikt",
        "password"             => "Lösenord",
        "password_confirmation"=> "Bekräfta lösenord",
        "create_user"          => "Skapa administratör",
        "creation_success"     => "Administratören har skapats! Du kan nu gå vidare till nästa steg.",
        "error_name_required"  => "Fullständigt namn krävs",
        "error_email_required" => "E-postadress måste anges",
        "error_email_invalid"  => "Ange en giltig e-postadress",
        "error_login_required" => "Användarnamn obligatoriskt",
        "error_password_required"=> "Lösenord krävs",
        "error_password_length"=> "Lösenordet måste vara minst 8 tecken långt",
        "error_password_mismatch"=> "Lösenorden matchar inte",
    ],

    "site_info" => [
        "heading"            => "Sajtens konfigurering",
        "subheading"         => "Konfigurera de grundläggande inställningarna för din webbplats",
        "name"               => "Webbplatsens namn",
        "description"        => "Webbplatsbeskrivning",
        "keywords"           => "Nyckelord",
        "keywords_help"      => "Separera sökord med kommatecken (t.ex. spel, servrar, Flute)",
        "url"                => "Sajt-URL",
        "url_help"           => "Fullständig URL på din webbplats, inklusive http:// eller https://",
        "timezone"           => "Tidszon",
        "footer_description" => "Webbplatsens Beskrivning",
        "footer_help"        => "Valfri text att visa i sidfoten",
        "tab_basics"         => "Allmänt",
        "tab_seo"            => "SEO",
        "basic_section"      => "Allmän information",
        "seo_section"        => "Sökmotoroptimering",
        "advanced_section"   => "Avancerade Inst&auml;llningar",
        "meta_title"         => "SEO titel",
        "meta_description"   => "SEO Beskrivning",
        "seo_preview"        => "Hur detta kommer att visas i sökningen",
        "seo_tips_title"     => "SEO Tips",
        "seo_tips_content"   => "Använd nyckelord i början av titeln. Optimal titellängd är 50-60 tecken. Beskrivningen ska vara informativ och innehålla en uppmaning till åtgärder inom 150-160 tecken.",
    ],

    "site_settings" => [
        "heading"               => "Slutgiltiga inställningar",
        "subheading"            => "Låt oss konfigurera webbplatsens huvudinställningar; du kan alltid ändra dem senare",
        "tab_general"           => "Allmänt",
        "tab_security"          => "Säkerhet",
        "general_section"       => "Webbplatsinställningar",
        "appearance_section"    => "Utseende",
        "security_section"      => "Säkerhetsinställningar",
        "cron_mode"             => "Cronläge",
        "cron_mode_desc"        => "Aktivera cron-läge. Du måste ställa in crontab för att cron-uppgifter ska fungera.",
        "maintenance_mode"      => "Underhållsläge",
        "maintenance_mode_desc" => "Webbplatsen kommer endast att vara tillgänglig för administratörer medan du konfigurerar den",
        "tips"                  => "Gränssnitt tips",
        "tips_desc"             => "Visa användbara tips och tips när du använder admin-panelen",
        "share"                 => "Dela fel",
        "share_desc"            => "Skicka CMS-fel till utvecklarservern",
        "flute_copyright"       => "Omnämnande av flöjt",
        "flute_copyright_desc"  => "En liten länk till flöjt CMS i sidfoten",
        "csrf_enabled"          => "CSRF skydd",
        "csrf_enabled_desc"     => "Skyddar din webbplats från cross-site begäran förfalskning. Vi rekommenderar att hålla den aktiverad",
        "convert_to_webp"       => "WebP bilder",
        "convert_to_webp_desc"  => "Konvertera automatiskt uppladdade bilder till WebP-format för att snabba upp webbplatsen",
        "robots"                => "Inställningar för sökmotor",
        "robots_desc"           => "Berätta för sökmotorer hur du hanterar din webbplats",
        "robots_index_follow"   => "Indexera webbplatsen och följ länkar",
        "robots_index_nofollow" => "Indexera webbplatsen, men följ inte länkar",
        "robots_noindex_follow" => "Indexera inte sajten utan följ länkar",
        "robots_noindex_nofollow"=> "Indexera inte webbplatsen och följ inte länkar",
    ],
];
