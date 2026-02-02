<?php

return [
    'title' => [
        'list' => 'APIキー',
        'description' => '外部アクセス用のAPIキーを管理',
        'create' => 'APIキーを作成',
        'edit' => 'APIキーを編集',
    ],
    'fields' => [
        'key' => [
            'label' => 'APIキー',
            'placeholder' => 'APIキーを入力',
            'help' => 'このキーはAPI認証に使用されます',
        ],
        'name' => [
            'label' => '名前',
            'placeholder' => 'キー名を入力',
            'help' => 'この名前を使用してキーを識別できます',
        ],
        'permissions' => [
            'label' => '権限',
        ],
        'created_at' => '作成日',
        'last_used_at' => '最終使用日',
        'never' => '未使用',
    ],
    'buttons' => [
        'actions' => '操作',
        'add' => 'キーを追加',
        'save' => '保存',
        'edit' => '編集',
        'delete' => '削除',
    ],
    'confirms' => [
        'delete_key' => 'このAPIキーを削除してもよろしいですか？',
    ],
    'messages' => [
        'save_success' => 'APIキーが正常に保存されました。',
        'key_not_found' => 'APIキーが見つかりません。',
        'no_permissions' => '少なくとも1つの権限を選択してください。',
        'update_success' => 'APIキーが正常に更新されました。',
        'update_error' => 'APIキーの更新エラー: :message',
        'delete_success' => 'APIキーが正常に削除されました。',
        'delete_error' => 'APIキーの削除エラー: :message',
    ],
];
