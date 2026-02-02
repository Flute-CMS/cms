<?php

return [
    'title' => [
        'list' => '通貨',
        'edit' => '通貨を編集',
        'create' => '通貨を作成',
        'description' => 'このページにはすべてのシステム通貨が表示されます',
        'main_info' => '基本情報',
        'actions' => '操作',
        'actions_description' => '通貨の操作',
    ],

    'fields' => [
        'name' => [
            'label' => '名前',
            'placeholder' => '通貨名を入力',
        ],
        'code' => [
            'label' => 'コード',
            'placeholder' => '通貨コードを入力',
            'help' => '一意の通貨コード（例：USD、EUR、JPY）',
        ],
        'minimum_value' => [
            'label' => '最小金額',
            'placeholder' => '最小金額を入力',
            'help' => 'この通貨の最小チャージ金額',
        ],
        'rate' => [
            'label' => 'レート',
            'placeholder' => '通貨レートを入力',
            'help' => '基準通貨に対するレート',
        ],
        'enabled' => [
            'label' => '有効',
            'help' => '有効な通貨はシステムで使用できます',
        ],
        'created_at' => '作成日',
        'updated_at' => '更新日',
    ],

    'status' => [
        'active' => 'アクティブ',
        'inactive' => '非アクティブ',
        'default' => 'デフォルト',
    ],

    'buttons' => [
        'add' => '通貨を追加',
        'save' => '保存',
        'cancel' => 'キャンセル',
        'delete' => '削除',
        'edit' => '編集',
        'actions' => '操作',
        'update_rates' => 'レートを更新',
    ],

    'messages' => [
        'currency_not_found' => '通貨が見つかりません。',
        'save_success' => '通貨が正常に保存されました。',
        'delete_success' => '通貨が正常に削除されました。',
        'update_rates_success' => '通貨レートが正常に更新されました。',
        'default_currency_delete' => 'デフォルト通貨は削除できません。',
        'no_permission.manage' => '通貨を管理する権限がありません。',
        'no_permission.delete' => '通貨を削除する権限がありません。',
    ],

    'confirms' => [
        'delete_currency' => 'この通貨を削除してもよろしいですか？この操作は元に戻せません。',
        'set_default' => 'この通貨をデフォルトに設定してもよろしいですか？すべてのレートが再計算されます。',
    ],
];
