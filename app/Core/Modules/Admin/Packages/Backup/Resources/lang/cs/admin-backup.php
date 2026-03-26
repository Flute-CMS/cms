<?php

return [
    'title' => 'Zálohy',
    'description' => 'Správa záloh modulů, motivů a CMS',

    'table' => [
        'type' => 'Typ',
        'name' => 'Název',
        'filename' => 'Soubor',
        'size' => 'Velikost',
        'date' => 'Vytvořeno',
        'actions' => 'Akce',
        'empty' => 'Zatím žádné zálohy',
    ],

    'types' => [
        'module' => 'Modul',
        'theme' => 'Motiv',
        'modules' => 'Všechny moduly',
        'themes' => 'Všechny motivy',
        'cms' => 'CMS',
        'full' => 'Úplná záloha',
        'vendor' => 'Vendor',
        'composer' => 'Composer',
    ],

    'metrics' => [
        'total_backups' => 'Celkem záloh',
        'total_size' => 'Celková velikost',
    ],

    'actions' => [
        'backup_module' => 'Zálohovat modul',
        'backup_theme' => 'Zálohovat motiv',
        'backup_all_modules' => 'Zálohovat všechny moduly',
        'backup_all_themes' => 'Zálohovat všechny motivy',
        'backup_cms' => 'Zálohovat jádro CMS',
        'backup_full' => 'Úplná záloha',
        'download' => 'Stáhnout',
        'delete' => 'Smazat',
        'restore' => 'Obnovit',
        'refresh' => 'Aktualizovat',
        'create_backup' => 'Vytvořit zálohu',
    ],

    'modal' => [
        'backup_module_title' => 'Vytvořit zálohu modulu',
        'backup_theme_title' => 'Vytvořit zálohu motivu',
        'select_module' => 'Vybrat modul',
        'select_theme' => 'Vybrat motiv',
    ],

    'confirmations' => [
        'backup_all_modules' => 'Opravdu chcete zálohovat všechny moduly?',
        'backup_all_themes' => 'Opravdu chcete zálohovat všechny motivy?',
        'backup_cms' => 'Opravdu chcete zálohovat jádro CMS?',
        'backup_full' => 'Opravdu chcete vytvořit úplnou zálohu? To může nějakou dobu trvat.',
        'delete' => 'Opravdu chcete tuto zálohu smazat?',
        'restore' => 'Opravdu chcete obnovit z této zálohy? Aktuální soubory budou přepsány.',
    ],

    'messages' => [
        'backup_created' => 'Záloha vytvořena: :filename',
        'backup_error' => 'Chyba zálohy: :message',
        'backup_deleted' => 'Záloha smazána',
        'delete_error' => 'Chyba při mazání: :message',
        'download_error' => 'Chyba při stahování: :message',
        'list_refreshed' => 'Seznam aktualizován',
        'restore_success' => 'Záloha úspěšně obnovena. Cache vymazána.',
        'restore_error' => 'Chyba při obnovení: :message',
    ],

    'errors' => [
        'module_not_found' => 'Modul nenalezen',
        'module_path_not_found' => 'Adresář modulu nenalezen',
        'theme_path_not_found' => 'Adresář motivu nenalezen',
        'modules_path_not_found' => 'Adresář modulů nenalezen',
        'themes_path_not_found' => 'Adresář motivů nenalezen',
        'cannot_create_zip' => 'Nelze vytvořit ZIP archiv',
        'cannot_open_zip' => 'Nelze otevřít ZIP archiv',
        'backup_not_found' => 'Záloha nenalezena',
        'cannot_determine_destination' => 'Nelze určit cíl obnovy',
        'unknown_backup_type' => 'Neznámý typ zálohy',
    ],
];
