<?php

return [
    'title' => 'ナビゲーション',
    'description' => 'このページにはFluteで作成されたすべてのナビゲーション項目が表示されます',
    'table' => [
        'title' => 'タイトル',
        'actions' => '操作',
    ],
    'buttons' => [
        'create' => '項目を作成',
        'edit' => '編集',
        'delete' => '削除',
    ],
    'modal' => [
        'item' => [
            'create_title' => 'ナビゲーション項目を作成',
            'edit_title' => 'ナビゲーション項目を編集',
            'fields' => [
                'title' => [
                    'label' => 'タイトル',
                    'placeholder' => '項目タイトルを入力',
                    'help' => 'ナビゲーション項目のタイトル',
                ],
                'description' => [
                    'label' => '説明',
                    'placeholder' => '項目の説明を入力（オプション）',
                    'help' => 'ナビゲーション項目のオプション説明',
                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'URLを入力（例：/home）',
                    'help' => 'リンクアドレス。項目に子要素がある場合は空のままにします。',
                ],
                'new_tab' => [
                    'label' => '新しいタブで開く',
                    'help' => 'URLが設定されている場合のみ機能します',
                ],
                'icon' => [
                    'label' => 'アイコン',
                    'placeholder' => 'アイコンを入力（例：ph.regular.house）',
                ],
                'visibility_auth' => [
                    'label' => '表示設定',
                    'help' => 'このナビゲーション項目を表示できるユーザー',
                    'options' => [
                        'all' => 'すべて',
                        'guests' => 'ゲストのみ',
                        'logged_in' => 'ログイン済みのみ',
                    ],
                ],
                'visibility' => [
                    'label' => '表示タイプ',
                    'help' => 'この項目が表示される場所',
                    'options' => [
                        'all' => 'すべて',
                        'desktop' => 'デスクトップのみ',
                        'mobile' => 'モバイルのみ',
                    ],
                ],
            ],
            'roles' => [
                'title' => '役割',
                'help' => 'この項目を表示できる役割。選択されていない場合、すべてのユーザーに表示されます',
            ],
        ],
    ],
    'confirms' => [
        'delete_item' => 'このナビゲーション項目を削除してもよろしいですか？',
    ],
    'messages' => [
        'invalid_sort_data' => '無効なソートデータ。',
        'item_created' => 'ナビゲーション項目が正常に作成されました。',
        'item_updated' => 'ナビゲーション項目が正常に更新されました。',
        'item_deleted' => 'ナビゲーション項目が正常に削除されました。',
        'item_not_found' => 'ナビゲーション項目が見つかりません。',
    ],
];
