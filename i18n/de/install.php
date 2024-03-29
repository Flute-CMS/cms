<?php

return [
    "back" => "Zurück",
    "next" => "Weiter",
    "last_step_required" => "Um fortzufahren, müssen Sie den letzten Schritt abschließen!",
    "finish" => "Installation abschließen!",
    "1" => [
        'card_head' => 'Sprachauswahl',
        "title" => "Flöte :: Sprachauswahl",
        'Несуществующий язык' => 'Es sieht so aus, als hätten Sie eine mysteriöse Sprache ausgewählt :0'
    ],
    2 => [
        "title" => "Flöte :: Anforderungsprüfung",
        'card_head' => "Kompatibilität",
        'card_head_desc' => "Auf dieser Seite müssen Sie die Einhaltung aller Anforderungen überprüfen und bei positivem Ergebnis mit der Installation fortfahren",
        'req_not_completed' => "Anforderungen nicht erfüllt",
        'need_to_install' => "Installation erforderlich",
        'may_installed' => "Empfohlene Installation",
        'installed' => "Installiert",
        'all_good' => "Alles gut!",
        'may_unstable' => "Kann instabil funktionieren",
        'min_php_7' => "Mindestens PHP-Version 7.4 erforderlich!",
        'php_exts' => "PHP-Erweiterungen",
        'other' => 'Andere'
    ],
    3 => [
        "title" => "Flöte :: Datenbankeingabe",
        'card_head' => "Datenbankverbindung",
        'card_head_desc' => "Füllen Sie alle Felder mit Daten aus Ihrer Datenbank aus. Es ist ratsam, eine neue Datenbank zu erstellen.",
        "driver" => "Datenbanktreiber auswählen",
        "ip" => "Datenbankhost eingeben",
        "port" => "Datenbankport eingeben",
        "db" => "Datenbankname eingeben",
        "user" => "Datenbankbenutzer eingeben",
        "pass" => "Datenbankpasswort eingeben",
        'db_error' => "Ein Fehler ist beim Verbinden aufgetreten: <br>%error%",
        'data_invalid' => "Die eingegebenen Daten sind ungültig!",
        "check_data" => "Daten überprüfen",
        "data_correct" => 'Daten korrekt'
    ],
    4 => [
        "title" => "Flöte :: Datenmigration",
        'card_head' => "Datenmigration",
        'card_head_desc' => "Müssen Daten aus einem anderen CMS migriert werden? Wählen Sie das erforderliche CMS aus (falls erforderlich)",
        'migrate_from' => 'Daten migrieren von',
        'thanks_but_no' => 'Danke, aber nein',
        'card_head_2' => 'Datenmigration von %cms%',
        'card_desc_2' => 'Wählen Sie die erforderlichen Arten der Migration aus und füllen Sie das Formular aus',
        'migrate' => [
            'all' => 'Alle migrieren',
            'servers' => 'Server migrieren',
            'admins' => 'Admins migrieren',
            'gateways' => 'Zahlungsgateways migrieren',
            'payments' => 'Zahlungsverlauf migrieren',
        ]
    ],
    5 => [
        "title" => "Flöte :: Besitzerregistrierung",
        'card_head' => "Besitzerregistrierung",
        'card_head_desc' => "Füllen Sie alle Felder mit Daten aus, um Ihr Konto zu erstellen.",
        'login' => 'Login',
        'login_placeholder' => 'Login eingeben',
        'name' => 'Spitzname',
        'name_placeholder' => 'Anzeigenamen eingeben',
        'email' => 'E-Mail',
        'email_placeholder' => 'E-Mail eingeben',
        'password' => 'Passwort',
        'password_placeholder' => 'Passwort eingeben',
        'repassword' => 'Passwort erneut eingeben',
        'repassword_placeholder' => 'Passwort erneut eingeben',
        'login_length' => 'Mindestlänge des Logins beträgt 2 Buchstaben!',
        'name_length' => 'Mindestlänge des Spitznamens beträgt 2 Buchstaben!',
        'pass_length' => 'Mindestlänge des Passworts beträgt 4 Zeichen!',
        'invalid_email' => 'Geben Sie die E-Mail-Adresse korrekt ein!',
        'pass_diff' => 'Die eingegebenen Passwörter stimmen nicht überein!',
        'error_create_user' => 'Fehler beim Erstellen des Benutzers!',
    ],
    6 => [
        "title" => "Flöte :: Sind Tooltips aktiviert?",
        'card_head' => "Tooltips aktivieren",
        'card_head_desc' => "Benötigen Sie Tooltips im System, um zu verstehen, wie bestimmte Funktionen verwendet werden?",
        'yes' => 'Ja, aktivieren, ich bin zum ersten Mal hier (empfohlen) 🤯',
        'no' => 'Nein, ich habe diese Flöte überall gedreht 😎'
    ],
    7 => [
        "title" => "Flöte :: Fehlerberichterstattung",
        'card_head' => "Fehlerberichterstattung aktivieren",
        'card_head_desc' => "Im Falle einer Fehlfunktion des Systems werden Fehler zur Verarbeitung an unseren Server gesendet. Nach einiger Zeit kann ein Update mit einer Fehlerbehebung veröffentlicht werden - danke an Sie 🥰",
        'yes' => 'Ja, senden Sie Fehler, um die Systemleistung zu verbessern 😇',
        'no' => 'Nein, nichts senden, es interessiert mich nicht 🤐'
    ],
];
