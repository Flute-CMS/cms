<?php

return [
    'title' => [
        'themes' => 'テーマ',
        'description' => 'このページでテーマとその設定を管理できます',
        'edit' => 'テーマを編集: :name',
        'create' => 'テーマを追加',
    ],
    'table' => [
        'name' => '名前',
        'version' => 'バージョン',
        'status' => 'ステータス',
        'actions' => '操作',
    ],
    'fields' => [
        'name' => [
            'label' => '名前',
            'placeholder' => 'テーマ名を入力',
        ],
        'version' => [
            'label' => 'バージョン',
            'placeholder' => 'テーマバージョンを入力',
        ],
        'enabled' => [
            'label' => '有効',
            'help' => 'このテーマを有効または無効にする',
        ],
        'description' => [
            'label' => '説明',
            'placeholder' => 'テーマの説明を入力',
        ],
        'author' => [
            'label' => '作成者',
            'placeholder' => 'テーマ作成者を入力',
        ],
    ],
    'buttons' => [
        'save' => '保存',
        'edit' => '編集',
        'delete' => '削除',
        'enable' => '有効化',
        'disable' => '無効化',
        'refresh' => 'テーマリストを更新',
        'details' => '詳細',
        'install' => 'インストール',
    ],
    'status' => [
        'active' => 'アクティブ',
        'inactive' => '非アクティブ',
        'not_installed' => '未インストール',
    ],
    'confirms' => [
        'delete' => 'このテーマを削除してもよろしいですか？',
        'install' => 'このテーマをインストールしてもよろしいですか？',
    ],
    'messages' => [
        'save_success' => 'テーマが正常に保存されました。',
        'save_error' => 'テーマの保存エラー: :message',
        'delete_success' => 'テーマが正常に削除されました。',
        'delete_error' => 'テーマの削除エラー: :message',
        'toggle_success' => 'テーマのステータスが正常に変更されました。',
        'toggle_error' => 'テーマのステータス変更エラー。',
        'not_found' => 'テーマが見つかりません。',
        'refresh_success' => 'テーマリストが正常に更新されました。',
        'install_success' => 'テーマが正常にインストールされました。',
        'install_error' => 'テーマのインストールエラー: :message',
        'enable_success' => 'テーマが正常に有効化されました。',
        'enable_error' => 'テーマの有効化エラー: :message',
        'disable_success' => 'テーマが正常に無効化されました。',
        'disable_error' => 'テーマの無効化エラー: :message',
    ],
];
