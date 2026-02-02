<?php

return [
    'title' => 'Säkerhetskopior',
    'description' => 'Hantera säkerhetskopior av moduler, teman och CMS',

    'table' => [
        'type' => 'Typ',
        'name' => 'Namn',
        'filename' => 'Fil',
        'size' => 'Storlek',
        'date' => 'Skapad',
        'actions' => 'Åtgärder',
        'empty' => 'Inga säkerhetskopior ännu',
    ],

    'types' => [
        'module' => 'Modul',
        'theme' => 'Tema',
        'modules' => 'Alla moduler',
        'themes' => 'Alla teman',
        'cms' => 'CMS',
        'full' => 'Fullständig säkerhetskopiering',
        'vendor' => 'Vendor',
        'composer' => 'Composer',
    ],

    'metrics' => [
        'total_backups' => 'Totalt antal säkerhetskopior',
        'total_size' => 'Total storlek',
    ],

    'actions' => [
        'backup_module' => 'Säkerhetskopiera modul',
        'backup_theme' => 'Säkerhetskopiera tema',
        'backup_all_modules' => 'Säkerhetskopiera alla moduler',
        'backup_all_themes' => 'Säkerhetskopiera alla teman',
        'backup_cms' => 'Säkerhetskopiera CMS-kärnan',
        'backup_full' => 'Fullständig säkerhetskopiering',
        'download' => 'Ladda ner',
        'delete' => 'Ta bort',
        'restore' => 'Återställ',
        'refresh' => 'Uppdatera',
        'create_backup' => 'Skapa säkerhetskopia',
    ],

    'modal' => [
        'backup_module_title' => 'Skapa säkerhetskopia av modul',
        'backup_theme_title' => 'Skapa säkerhetskopia av tema',
        'select_module' => 'Välj modul',
        'select_theme' => 'Välj tema',
    ],

    'confirmations' => [
        'backup_all_modules' => 'Är du säker på att du vill säkerhetskopiera alla moduler?',
        'backup_all_themes' => 'Är du säker på att du vill säkerhetskopiera alla teman?',
        'backup_cms' => 'Är du säker på att du vill säkerhetskopiera CMS-kärnan?',
        'backup_full' => 'Är du säker på att du vill skapa en fullständig säkerhetskopia? Detta kan ta en stund.',
        'delete' => 'Är du säker på att du vill ta bort denna säkerhetskopia?',
        'restore' => 'Är du säker på att du vill återställa från denna säkerhetskopia? Nuvarande filer kommer att skrivas över.',
    ],

    'messages' => [
        'backup_created' => 'Säkerhetskopia skapad: :filename',
        'backup_error' => 'Säkerhetskopieringsfel: :message',
        'backup_deleted' => 'Säkerhetskopia borttagen',
        'delete_error' => 'Borttagningsfel: :message',
        'download_error' => 'Nedladdningsfel: :message',
        'list_refreshed' => 'Listan uppdaterad',
        'restore_success' => 'Säkerhetskopian återställd framgångsrikt. Cache rensad.',
        'restore_error' => 'Återställningsfel: :message',
    ],

    'errors' => [
        'module_not_found' => 'Modulen hittades inte',
        'module_path_not_found' => 'Modulkatalogen hittades inte',
        'theme_path_not_found' => 'Temakatalogen hittades inte',
        'modules_path_not_found' => 'Modulkatalogen hittades inte',
        'themes_path_not_found' => 'Temakatalogen hittades inte',
        'cannot_create_zip' => 'Kan inte skapa ZIP-arkiv',
        'cannot_open_zip' => 'Kan inte öppna ZIP-arkiv',
        'backup_not_found' => 'Säkerhetskopian hittades inte',
        'cannot_determine_destination' => 'Kan inte bestämma återställningsmål',
        'unknown_backup_type' => 'Okänd säkerhetskopietyp',
    ],
];
