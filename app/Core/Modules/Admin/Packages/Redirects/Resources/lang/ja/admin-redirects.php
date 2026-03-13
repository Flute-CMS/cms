<?php

return [
    'title' => 'リダイレクト',
    'description' => '条件付きURLリダイレクトの管理',

    'fields' => [
        'from_url' => [
            'label' => 'リダイレクト元',
            'placeholder' => '/old-page',
            'help' => 'リダイレクト元のURLパス（例：/old-page）',
        ],
        'to_url' => [
            'label' => 'リダイレクト先',
            'placeholder' => '/new-page',
            'help' => 'リダイレクト先のURL',
        ],
        'conditions' => [
            'label' => '条件',
            'help' => 'リダイレクトをトリガーするためのオプション条件',
        ],
        'condition_type' => [
            'label' => 'タイプ',
            'placeholder' => '条件タイプを選択',
        ],
        'condition_operator' => [
            'label' => '演算子',
            'placeholder' => '演算子を選択',
        ],
        'condition_value' => [
            'label' => '値',
            'placeholder' => '値を入力',
        ],
    ],

    'condition_types' => [
        'ip' => 'IPアドレス',
        'cookie' => 'Cookie',
        'referer' => 'リファラー',
        'request_method' => 'HTTPメソッド',
        'user_agent' => 'ユーザーエージェント',
        'header' => 'HTTPヘッダー',
        'lang' => '言語',
    ],

    'operators' => [
        'equals' => '等しい',
        'not_equals' => '等しくない',
        'contains' => '含む',
        'not_contains' => '含まない',
    ],

    'buttons' => [
        'add' => 'リダイレクトを追加',
        'save' => '保存',
        'edit' => '編集',
        'delete' => '削除',
        'actions' => 'アクション',
        'add_condition_group' => '条件グループを追加',
        'add_condition' => '条件を追加',
        'remove_condition' => '削除',
        'clear_cache' => 'キャッシュをクリア',
    ],

    'messages' => [
        'save_success' => 'リダイレクトが正常に保存されました。',
        'update_success' => 'リダイレクトが正常に更新されました。',
        'delete_success' => 'リダイレクトが正常に削除されました。',
        'not_found' => 'リダイレクトが見つかりません。',
        'cache_cleared' => 'リダイレクトキャッシュが正常にクリアされました。',
        'route_conflict' => '警告：URL ":url" は既存のルート ":route" と競合しています。ルートが優先されるため、リダイレクトが期待通りに動作しない可能性があります。',
        'from_url_required' => '「リダイレクト元」フィールドは必須です。',
        'to_url_required' => '「リダイレクト先」フィールドは必須です。',
        'same_urls' => '「リダイレクト元」と「リダイレクト先」は同じにできません。',
    ],

    'empty' => [
        'title' => 'リダイレクトはまだありません',
        'sub' => '最初のリダイレクトを作成してURL転送の管理を開始しましょう',
    ],

    'confirms' => [
        'delete' => 'このリダイレクトを削除してもよろしいですか？この操作は元に戻せません。',
    ],

    'table' => [
        'from' => 'リダイレクト元',
        'to' => 'リダイレクト先',
        'conditions' => '条件',
        'actions' => 'アクション',
    ],

    'modal' => [
        'create_title' => 'リダイレクトの作成',
        'edit_title' => 'リダイレクトの編集',
        'conditions_title' => 'リダイレクト条件',
        'conditions_help' => '条件グループ間はOR論理、グループ内はAND論理が使用されます。',
        'group_label' => 'グループ :number',
    ],

    'settings' => [
        'title' => '設定',
        'cache_time' => [
            'label' => 'キャッシュ時間（秒）',
            'help' => 'リダイレクトルールのキャッシュ期間。無効にするには0を設定。',
            'placeholder' => '3600',
        ],
    ],

    'alert' => [
        'route_conflict_title' => 'ルートの競合が検出されました',
    ],
];
