<?php

return [
    'search_servers' => 'サーバーを検索',
    'title' => [
        'list' => 'サーバー',
        'edit' => 'サーバーを編集',
        'create' => 'サーバーを追加',
        'description' => 'Fluteに追加されたすべてのサーバーがここにリストされています',
        'main_info' => '基本情報',
        'actions' => 'アクション',
        'actions_description' => 'サーバー上のアクション',
        'integrations' => '統合',
    ],
    'tabs' => [
        'main' => 'メイン',
        'db_connections' => 'DB統合',
    ],
    'fields' => [
        'name' => [
            'label' => '名前',
            'placeholder' => 'サーバー名を入力',
        ],
        'ip' => [
            'label' => 'IPアドレス',
            'placeholder' => '127.0.0.1',
        ],
        'port' => [
            'label' => 'ポート',
            'placeholder' => '27015',
        ],
        'mod' => [
            'label' => 'ゲーム',
            'placeholder' => 'ゲームを選択',
        ],
        'rcon' => [
            'label' => 'RCONパスワード',
            'placeholder' => 'RCONパスワードを入力',
            'help' => 'リモートサーバー管理用のパスワード',
        ],
        'display_ip' => [
            'label' => '表示IP',
            'placeholder' => '127.0.0.1:27015',
            'help' => 'ユーザーに表示されるIPアドレス',
        ],
        'ranks' => [
            'label' => 'ランクパック',
            'placeholder' => 'ランクパックを選択',
        ],
        'ranks_format' => [
            'label' => 'ランクファイル形式',
            'placeholder' => 'ランクファイル形式を選択',
        ],
        'ranks_premier' => [
            'label' => 'プレミアランク',
            'placeholder' => 'サーバーでプレミアランクを使用するか',
        ],
        'query_port' => [
            'label' => 'クエリポート',
            'placeholder' => 'オプション。空の場合は接続ポートを使用',
        ],
        'rcon_port' => [
            'label' => 'RCONポート',
            'placeholder' => 'オプション。空の場合は接続ポートを使用',
        ],
        'lat' => [
            'label' => '緯度',
            'help' => 'Ping計算用のサーバーの地理的緯度',
        ],
        'lon' => [
            'label' => '経度',
            'help' => 'Ping計算用のサーバーの地理的経度',
        ],
        'enabled' => [
            'label' => '有効',
            'help' => 'サーバーを公開リストに表示するか',
        ],
        'created_at' => '作成日',
    ],
    'status' => [
        'active' => 'アクティブ',
        'inactive' => '非アクティブ',
        'online' => 'サーバーオンライン',
        'offline' => 'サーバーオフライン',
        'hostname' => 'ホスト名',
        'map' => 'マップ',
        'players' => 'プレイヤー',
        'game' => 'ゲーム',
        'status' => 'ステータス',
        'and_more' => '+:count 人',
    ],
    'db_connection' => [
        'title' => 'DB統合',
        'fields' => [
            'mod' => [
                'label' => '統合',
                'placeholder' => '統合を選択',
                'help' => '統合を選択（統計、BAN、VIPなど）。',
            ],
            'dbname' => [
                'label' => '接続',
                'placeholder' => '接続を選択',
                'help' => '設定 → データベースで作成。',
            ],
            'driver' => [
                'label' => 'ドライバー',
                'placeholder' => 'ドライバーを選択',
                'custom' => 'カスタム',
            ],
            'additional' => [
                'label' => '追加設定',
                'placeholder' => '追加設定を入力',
            ],
            'params' => 'パラメータ',
            'custom_driver_name' => [
                'label' => 'ドライバー名',
                'placeholder' => 'ドライバー名を入力',
            ],
            'json_settings' => [
                'label' => 'JSON設定',
                'placeholder' => 'JSONで設定を入力',
                'help' => '任意のJSON設定を入力',
            ],
        ],
        'add' => [
            'title' => 'DB統合を追加',
            'button' => '統合を追加',
        ],
        'edit' => [
            'title' => 'DB統合を編集',
        ],
        'create_db' => [
            'title' => 'データベース接続なし',
            'description' => '統合をリンクするには、まず接続を作成してください。',
            'note' => '接続は保存後に利用可能になります。',
            'button' => '接続を作成',
        ],
        'delete' => [
            'confirm' => 'この接続を削除してもよろしいですか？',
        ],
    ],
    'db_drivers' => [
        'default' => [
            'name' => 'デフォルト',
            'fields' => [
                'connection' => [
                    'label' => '接続',
                    'placeholder' => 'DB接続を選択',
                    'help' => '設定からデータベース接続を選択',
                ],
                'table_prefix' => [
                    'label' => 'テーブルプレフィックス',
                    'placeholder' => 'テーブルプレフィックスを入力',
                    'help' => 'データベーステーブルのプレフィックス',
                ],
            ],
        ],
        'statistics' => [
            'name' => '統計',
            'fields' => [
                'connection' => [
                    'label' => '接続',
                    'placeholder' => 'DB接続を選択',
                    'help' => '設定からデータベース接続を選択',
                ],
                'table_prefix' => [
                    'label' => 'テーブルプレフィックス',
                    'placeholder' => 'テーブルプレフィックスを入力',
                    'help' => 'データベーステーブルのプレフィックス',
                ],
                'player_table' => [
                    'label' => 'プレイヤーテーブル',
                    'placeholder' => 'プレイヤーテーブル名を入力',
                    'help' => 'プレイヤーデータを含むテーブル',
                ],
                'steam_id_field' => [
                    'label' => 'Steam IDフィールド',
                    'placeholder' => 'Steam IDフィールド名を入力',
                    'help' => 'Steam IDを含むフィールド',
                ],
                'name_field' => [
                    'label' => '名前フィールド',
                    'placeholder' => '名前フィールド名を入力',
                    'help' => 'プレイヤー名を含むフィールド',
                ],
            ],
        ],
        'no_drivers' => [
            'title' => '利用可能なDBドライバーなし',
            'description' => '登録されたデータベースドライバーが見つかりません。管理者に連絡してください。',
        ],
    ],
    'mods' => [
        'custom_settings_name' => [
            'title' => 'ドライバー名',
            'placeholder' => 'ドライバー名を入力',
        ],
        'custom_settings_json' => [
            'title' => '設定JSON',
            'placeholder' => 'JSON設定を入力',
        ],
        'custom_alert' => [
            'title' => '警告！',
            'description' => 'カスタム設定の入力には注意が必要です！確信が持てない場合は、カスタム設定を追加しないでください！',
        ],
        'custom' => 'カスタム',
    ],
    'buttons' => [
        'add' => '追加',
        'save' => '保存',
        'cancel' => 'キャンセル',
        'delete' => '削除',
        'edit' => '編集',
        'actions' => 'アクション',
        'test_connection' => '接続テスト',
    ],
    'messages' => [
        'server_not_found' => 'サーバーが見つかりません。',
        'connection_not_found' => '接続が見つかりません。',
        'save_success' => 'サーバーが正常に保存されました。',
        'delete_success' => 'サーバーが正常に削除されました。',
        'connection_add_success' => '接続が正常に追加されました。',
        'connection_update_success' => '接続が正常に更新されました。',
        'connection_delete_success' => '接続が正常に削除されました。',
        'save_server_first' => 'まずサーバーを保存してください。',
        'invalid_driver_settings' => '無効なドライバー設定です。',
        'no_permission.manage' => 'サーバーを管理する権限がありません。',
        'no_permission.delete' => 'サーバーを削除する権限がありません。',
        'invalid_json' => '無効なJSON形式です。',
        'server_deleted' => 'サーバーが正常に削除されました。',
        'server_updated' => 'サーバーが正常に更新されました。',
        'server_created' => 'サーバーが正常に作成されました。',
        'save_not_for_db_connections' => '保存は主要なサーバー情報のみです。',
        'invalid_ip' => 'ポートなしの有効なIPアドレスを入力してください。',
        'connection_success' => 'サーバーへの接続に成功しました。',
        'connection_failed' => 'サーバーへの接続に失敗しました',
        'connection_no_response' => 'サーバーがクエリに応答していません。',
    ],
    'confirms' => [
        'delete_server' => 'このサーバーを削除してもよろしいですか？この操作は元に戻せません。',
    ],
];
