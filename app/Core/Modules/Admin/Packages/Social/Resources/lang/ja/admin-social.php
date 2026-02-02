<?php

return [
    'title' => [
        'social' => 'ソーシャルネットワーク',
        'description' => 'このページで認証用のソーシャルネットワークを設定できます',
        'edit' => 'ソーシャルネットワークを編集: :name',
        'create' => 'ソーシャルネットワークを追加',
    ],
    'table' => [
        'social' => 'ソーシャルネットワーク',
        'cooldown' => 'クールダウン',
        'registration' => '登録',
        'status' => 'ステータス',
        'actions' => '操作',
    ],
    'fields' => [
        'icon' => [
            'label' => 'アイコン',
            'placeholder' => '例：ph.regular.steam',
        ],
        'allow_register' => [
            'label' => '登録を許可',
            'help' => 'このソーシャルネットワーク経由で登録可能',
        ],
        'cooldown_time' => [
            'label' => 'クールダウン時間',
            'help' => '例：3600（秒、1時間に相当）',
            'small' => '例：3600秒（1時間）',
            'placeholder' => '3600秒',
            'popover' => 'ソーシャルリンクの削除と再追加の間の時間',
        ],
        'redirect_uri' => [
            'first' => '最初のURI',
            'second' => '2番目のURI',
        ],
        'driver' => [
            'label' => '認証ドライバー',
            'placeholder' => 'ドライバーを選択',
        ],
        'client_id' => [
            'label' => 'クライアントID',
        ],
        'client_secret' => [
            'label' => 'クライアントシークレット',
        ],
    ],
    'buttons' => [
        'add' => '追加',
        'save' => '保存',
        'edit' => '編集',
        'delete' => '削除',
        'enable' => '有効化',
        'disable' => '無効化',
    ],
    'status' => [
        'active' => 'アクティブ',
        'inactive' => '非アクティブ',
    ],
    'confirms' => [
        'delete' => 'このソーシャルネットワークを削除してもよろしいですか？',
    ],
    'messages' => [
        'save_success' => 'ソーシャルネットワークが正常に保存されました。',
        'save_error' => '保存エラー: :message',
        'delete_success' => 'ソーシャルネットワークが正常に削除されました。',
        'delete_error' => '削除エラー: :message',
        'toggle_success' => 'ソーシャルネットワークのステータスが正常に変更されました。',
        'toggle_error' => 'ステータス変更エラー。',
        'not_found' => 'ソーシャルネットワークが見つかりません。',
    ],
    'edit' => [
        'default' => 'ドライバー :driver はテストされていません。正しく動作しない可能性があります。パラメータを手動で設定する必要があります。',
        'steam_success' => 'すべて正常です。セットアップは不要です。',
        'steam_error' => 'STEAM APIキーが設定されていません。<a href="/admin/main-settings" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">設定</a>で設定してください。',
    ],
    'no_drivers' => '利用可能なドライバーがありません。',
];
