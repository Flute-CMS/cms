<?php

return [
    'title' => 'バックアップ',
    'description' => 'モジュール、テーマ、CMSのバックアップを管理',

    'table' => [
        'type' => 'タイプ',
        'name' => '名前',
        'filename' => 'ファイル',
        'size' => 'サイズ',
        'date' => '作成日',
        'actions' => '操作',
        'empty' => 'まだバックアップがありません',
    ],

    'types' => [
        'module' => 'モジュール',
        'theme' => 'テーマ',
        'modules' => '全てのモジュール',
        'themes' => '全てのテーマ',
        'cms' => 'CMS',
        'full' => '完全バックアップ',
        'vendor' => 'Vendor',
        'composer' => 'Composer',
    ],

    'metrics' => [
        'total_backups' => '合計バックアップ数',
        'total_size' => '合計サイズ',
    ],

    'actions' => [
        'backup_module' => 'モジュールをバックアップ',
        'backup_theme' => 'テーマをバックアップ',
        'backup_all_modules' => '全てのモジュールをバックアップ',
        'backup_all_themes' => '全てのテーマをバックアップ',
        'backup_cms' => 'CMSコアをバックアップ',
        'backup_full' => '完全バックアップ',
        'download' => 'ダウンロード',
        'delete' => '削除',
        'restore' => '復元',
        'refresh' => '更新',
        'create_backup' => 'バックアップを作成',
    ],

    'modal' => [
        'backup_module_title' => 'モジュールのバックアップを作成',
        'backup_theme_title' => 'テーマのバックアップを作成',
        'select_module' => 'モジュールを選択',
        'select_theme' => 'テーマを選択',
    ],

    'confirmations' => [
        'backup_all_modules' => '全てのモジュールをバックアップしてもよろしいですか？',
        'backup_all_themes' => '全てのテーマをバックアップしてもよろしいですか？',
        'backup_cms' => 'CMSコアをバックアップしてもよろしいですか？',
        'backup_full' => '完全バックアップを作成してもよろしいですか？時間がかかる場合があります。',
        'delete' => 'このバックアップを削除してもよろしいですか？',
        'restore' => 'このバックアップから復元してもよろしいですか？現在のファイルは上書きされます。',
    ],

    'messages' => [
        'backup_created' => 'バックアップが作成されました: :filename',
        'backup_error' => 'バックアップエラー: :message',
        'backup_deleted' => 'バックアップが削除されました',
        'delete_error' => '削除エラー: :message',
        'download_error' => 'ダウンロードエラー: :message',
        'list_refreshed' => 'リストが更新されました',
        'restore_success' => 'バックアップが正常に復元されました。キャッシュがクリアされました。',
        'restore_error' => '復元エラー: :message',
    ],

    'errors' => [
        'module_not_found' => 'モジュールが見つかりません',
        'module_path_not_found' => 'モジュールディレクトリが見つかりません',
        'theme_path_not_found' => 'テーマディレクトリが見つかりません',
        'modules_path_not_found' => 'モジュールディレクトリが見つかりません',
        'themes_path_not_found' => 'テーマディレクトリが見つかりません',
        'cannot_create_zip' => 'ZIPアーカイブを作成できません',
        'cannot_open_zip' => 'ZIPアーカイブを開けません',
        'backup_not_found' => 'バックアップが見つかりません',
        'cannot_determine_destination' => '復元先を特定できません',
        'unknown_backup_type' => '不明なバックアップタイプ',
    ],
];
