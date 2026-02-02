<?php

return [
    'title' => 'フッター',
    'description' => 'フッター項目とソーシャルリンクを管理',
    'tabs' => [
        'main_elements' => 'メイン項目',
        'social' => 'ソーシャルネットワーク',
    ],
    'table' => [
        'title' => 'タイトル',
        'icon' => 'アイコン',
        'url' => 'URL',
        'actions' => '操作',
    ],
    'sections' => [
        'main_links' => [
            'title' => 'メインリンク',
            'description' => 'このページにはFluteで作成されたすべてのフッター項目が表示されます',
        ],
        'social_links' => [
            'title' => 'フッターのソーシャルリンク',
            'description' => 'このページにはサイトフッターに表示されるすべてのソーシャルネットワークが表示されます',
        ],
    ],
    'buttons' => [
        'create' => '作成',
        'edit' => '編集',
        'delete' => '削除',
    ],
    'modal' => [
        'footer_item' => [
            'create_title' => 'フッター項目を作成',
            'edit_title' => 'フッター項目を編集',
            'fields' => [
                'title' => [
                    'label' => 'タイトル',
                    'placeholder' => '項目タイトルを入力',
                    'help' => 'フッター項目のタイトル',
                ],
                'icon' => [
                    'label' => 'アイコン',
                    'placeholder' => 'アイコンを入力（例：ph.regular.home）',
                    'help' => 'アイコン識別子（オプション）',
                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'URLを入力（例：/contact）',
                    'help' => 'リンクアドレス。項目に子要素がある場合は空のままにします。',
                ],
                'new_tab' => [
                    'label' => '新しいタブで開く',
                    'help' => 'URLが設定されている場合のみ機能します',
                ],
            ],
        ],
        'social' => [
            'create_title' => 'ソーシャルネットワークを作成',
            'edit_title' => 'ソーシャルネットワークを編集',
            'fields' => [
                'name' => [
                    'label' => '名前',
                    'placeholder' => 'ソーシャルネットワーク名を入力',
                    'help' => 'ソーシャルネットワーク名（例：Discord）',
                ],
                'icon' => [
                    'label' => 'アイコン',
                    'placeholder' => 'アイコンを入力（例：ph.regular.discord-logo）',
                    'help' => 'アイコン識別子、例："ph.bold.discord-logo-bold"',
                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'URLを入力（例：https://discord.gg/yourpage）',
                    'help' => 'ソーシャルネットワークページへのリンク',
                ],
            ],
        ],
    ],
    'confirms' => [
        'delete_item' => 'このフッター項目を削除してもよろしいですか？',
        'delete_social' => 'このソーシャルネットワークを削除してもよろしいですか？',
    ],
    'messages' => [
        'invalid_sort_data' => '無効なソートデータ。',
        'item_created' => 'フッター項目が正常に作成されました。',
        'item_updated' => 'フッター項目が正常に更新されました。',
        'item_deleted' => 'フッター項目が正常に削除されました。',
        'item_not_found' => 'フッター項目が見つかりません。',
        'social_created' => 'ソーシャルネットワークが正常に作成されました。',
        'social_updated' => 'ソーシャルネットワークが正常に更新されました。',
        'social_deleted' => 'ソーシャルネットワークが正常に削除されました。',
        'social_not_found' => 'ソーシャルネットワークが見つかりません。',
    ],
];
