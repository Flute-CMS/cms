<?php

return [
    'title' => [
        'roles' => '役割',
        'roles_description' => 'ユーザー役割の管理。最上位の役割が最高の優先度を持ちます。',
    ],
    'breadcrumbs' => [
        'roles' => '役割',
    ],
    'buttons' => [
        'create' => '役割を作成',
        'edit' => '編集',
        'delete' => '削除',
        'save' => '保存',
        'update' => '更新',
    ],
    'table' => [
        'role_name' => '役割名',
        'actions' => '操作',
    ],
    'modal' => [
        'create' => [
            'title' => '役割を作成',
            'submit' => '作成',
        ],
        'edit' => [
            'title' => '役割を編集',
            'submit' => '更新',
        ],
        'delete' => [
            'title' => '役割を削除',
            'confirm' => 'この役割を削除してもよろしいですか？',
        ],
    ],
    'fields' => [
        'name' => [
            'label' => '役割名',
            'placeholder' => '役割名を入力',
            'help' => '役割の一意の名前',
        ],
        'color' => [
            'label' => '色',
            'help' => '役割に関連付けられた色',
        ],
        'permissions' => [
            'label' => '権限',
            'help' => 'この役割の権限を選択',
        ],
        'icon' => [
            'label' => 'アイコン',
            'placeholder' => 'ph.regular... または <svg...',
            'help' => '役割に関連付けられたアイコン',
        ],
    ],
    'messages' => [
        'created' => '役割が正常に作成されました。',
        'updated' => '役割が正常に更新されました。',
        'deleted' => '役割が正常に削除されました。',
        'not_found' => '役割が見つからないか、編集する権限がありません。',
        'invalid_sort' => '無効なソートデータ。',
        'no_permissions' => '少なくとも1つの権限を選択してください。',
    ],
];
