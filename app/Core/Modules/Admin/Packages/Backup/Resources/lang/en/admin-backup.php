<?php

return [
    'title' => 'Backups',
    'description' => 'Manage backups of modules, themes and CMS',

    'table' => [
        'type' => 'Type',
        'name' => 'Name',
        'filename' => 'File',
        'size' => 'Size',
        'date' => 'Created',
        'actions' => 'Actions',
        'empty' => 'No backups yet',
    ],

    'types' => [
        'module' => 'Module',
        'theme' => 'Theme',
        'modules' => 'All Modules',
        'themes' => 'All Themes',
        'cms' => 'CMS',
        'full' => 'Full Backup',
        'vendor' => 'Vendor',
        'composer' => 'Composer',
    ],

    'metrics' => [
        'total_backups' => 'Total Backups',
        'total_size' => 'Total Size',
    ],

    'actions' => [
        'backup_module' => 'Backup Module',
        'backup_theme' => 'Backup Theme',
        'backup_all_modules' => 'Backup All Modules',
        'backup_all_themes' => 'Backup All Themes',
        'backup_cms' => 'Backup CMS Core',
        'backup_full' => 'Full Backup',
        'download' => 'Download',
        'delete' => 'Delete',
        'restore' => 'Restore',
        'refresh' => 'Refresh',
        'create_backup' => 'Create Backup',
    ],

    'modal' => [
        'backup_module_title' => 'Create Module Backup',
        'backup_theme_title' => 'Create Theme Backup',
        'select_module' => 'Select Module',
        'select_theme' => 'Select Theme',
    ],

    'confirmations' => [
        'backup_all_modules' => 'Are you sure you want to backup all modules?',
        'backup_all_themes' => 'Are you sure you want to backup all themes?',
        'backup_cms' => 'Are you sure you want to backup the CMS core?',
        'backup_full' => 'Are you sure you want to create a full backup? This may take some time.',
        'delete' => 'Are you sure you want to delete this backup?',
        'restore' => 'Are you sure you want to restore from this backup? Current files will be overwritten.',
    ],

    'messages' => [
        'backup_created' => 'Backup created: :filename',
        'backup_error' => 'Backup error: :message',
        'backup_deleted' => 'Backup deleted',
        'delete_error' => 'Delete error: :message',
        'download_error' => 'Download error: :message',
        'list_refreshed' => 'List refreshed',
        'restore_success' => 'Backup restored successfully. Cache cleared.',
        'restore_error' => 'Restore error: :message',
    ],

    'errors' => [
        'module_not_found' => 'Module not found',
        'module_path_not_found' => 'Module directory not found',
        'theme_path_not_found' => 'Theme directory not found',
        'modules_path_not_found' => 'Modules directory not found',
        'themes_path_not_found' => 'Themes directory not found',
        'cannot_create_zip' => 'Cannot create ZIP archive',
        'cannot_open_zip' => 'Cannot open ZIP archive',
        'backup_not_found' => 'Backup not found',
        'cannot_determine_destination' => 'Cannot determine restore destination',
        'unknown_backup_type' => 'Unknown backup type',
    ],
];
