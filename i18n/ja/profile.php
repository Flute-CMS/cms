<?php

return [
    "edit" => [
        "title" => "プロファイルを編集",

        "main" => [
            "title"       => "メイン設定",
            "description" => "ここでは、アカウントの主な設定を変更できます。",
            "info_title"  => "基本情報",
            "info_description" => "一部のデータは他のユーザーに表示される可能性があります。",

            "fields" => [
                "name"                  => "名前",
                "email"                 => "Eメール",
                "password"              => "パスワード",
                "email_verified"        => "メールアドレスを認証しました",
                "email_not_verified"    => "メール認証がされていません",
                "password_not_set"      => "未設定",
                "password_not_provided" => "提供されていません",
                "last_changed"          => "最終更新",
            ],

            "password_description" => "強力なパスワードは、アカウントを保護するのに役立ちます。",

            "basic_information" => [
                "title"       => "基本情報",
                "description" => "プロフィールの基本情報を変更します。",

                "fields" => [
                    "name"                 => "名前",
                    "name_placeholder"     => "フルネームを入れなさい",
                    "name_info"            => "この名前はサイト上のすべてのユーザーに表示されます",

                    "login"                => "ユーザー名",
                    "login_placeholder"    => "ユーザーネームを入力してください",
                    "login_info"           => "あなたのユーザー名はあなたにのみ表示され、ログインに使用されています",

                    "uri"                  => "プロフィールリンク",
                    "uri_placeholder"      => "URLを入力してください",
                    "uri_info"             => "プロフィールURLのスラグを入力します。例: :example",

                    "email"                => "Eメール",
                    "email_placeholder"    => "メールアドレスを入力してください",
                ],

                "save_changes"         => "変更を保存",
                "save_changes_success" => "基本情報は正常に更新されました。",
            ],

            "profile_images" => [
                "title"       => "プロフィール画像",
                "description" => "プロフィールをパーソナライズするためにアバターとバナーをアップロードしてください。",

                "fields" => [
                    "avatar" => "アバター",
                    "banner" => "バナー",
                ],

                "save_changes"         => "画像を保存",
                "save_changes_success" => "プロフィール画像が正常に更新されました。",
            ],

            "change_password" => [
                "title"       => "パスワードの変更",
                "description" => "セキュリティ強化のために現在のパスワードを変更してください。",

                "fields" => [
                    "current_password"                => "現在のパスワード",
                    "current_password_placeholder"    => "現在のパスワードを入力",

                    "new_password"                    => "新パスワード",
                    "new_password_placeholder"        => "新しいパスワードを入力",

                    "confirm_new_password"            => "新しいパスワードの確認 ",
                    "confirm_new_password_placeholder"=> "新しいパスワードを再入力してください。",
                ],

                "save_changes"         => "パスワードを変更する",
                "save_changes_success" => "パスワードの変更に成功しました。",
                "current_password_incorrect" => "パスワードが違います",
                "passwords_do_not_match"      => "パスワードを適合しない",
            ],

            "delete_account" => [
                "title"       => "アカウントの削除",
                "description" => "アカウントを削除すると、すべてのデータが永久に失われます。",
                "confirm_message" => "アカウントを削除してもよろしいですか？すべてのデータは完全に削除されます。",

                "fields" => [
                    "confirmation"             => "削除確認",
                    "confirmation_placeholder" => "確認するにはユーザー名を入力してください",
                ],

                "delete_button"       => "アカウントの削除",
                "delete_success"      => "アカウントの削除が完了しました。",
                "delete_failed"       => "不正な確認です。アカウントは削除されませんでした。",
                "confirmation_error"  => "ユーザー名を正しく入力してください。",
            ],

            "profile_privacy" => [
                "title"       => "プロフィールのプライバシー",
                "description" => "プロフィールのプライバシー設定を設定します。",

                "fields" => [
                    "hidden"  => [
                        "label" => "公開",
                        "info"  => "あなたのプロフィールはすべてのユーザーに表示されます。",
                    ],
                    "visible" => [
                        "label" => "非公開",
                        "info"  => "あなたのプロフィールは他のユーザーから非表示になっています。",
                    ],
                ],

                "save_changes_success" => "プライバシー設定が正常に更新されました。",
            ],

            "profile_theme" => [
                "title"       => "システムテーマ",
                "description" => "システム全体のテーマを選択します。",

                "fields" => [
                    "light" => [
                        "label" => "ライトテーマ",
                        "info"  => "昼間に適しています。",
                    ],
                    "dark"  => [
                        "label" => "ダークテーマ",
                        "info"  => "夜の仕事に最適です。",
                    ],
                ],

                "save_changes"         => "テーマを保存",
                "save_changes_success" => "プロファイルテーマが正常に更新されました。",
            ],
        ],

        "settings" => [
            "title" => "値の受け渡し",
        ],

        "social" => [
            "title"               => "インテグレーション",
            "description"         => "迅速なログインと追加機能へのアクセスのためのソーシャルネットワークを接続します。",
            "unlink"              => "リンク解除",
            "unlink_description"  => "このソーシャルネットワークのリンクを解除してもよろしいですか？",
            "default_link"        => "デフォルトのリンク",
            "connect"             => "リンク",
            "no_socials"          => "残念ながら、私たちのシステムにはソーシャルネットワークがありません 😢",
            "show_description"    => "他のユーザーにソーシャルネットワークを表示",
            "hide_description"    => "他のユーザーからソーシャルネットワークを非表示にする",
            "last_social_network" => "ソーシャルネットワークのリンクを解除するには、パスワードを設定します。",
        ],

        "payments" => [
            "title"       => "支払い",
            "description" => "支払いと取引の歴史。",
            "table"       => [
                "id"          => "ID",
                "date"        => "デート",
                "gateway"     => "支払い方法",
                "amount"      => "数量",
                "status"      => "サーバの状態",
                "promo"       => "プロモーションコード",
                "transaction" => "取引",
                "actions"     => "動作",
            ],
            "status" => [
                "paid"    => "お支払い完了",
                "pending" => "処理待ち",
            ],
        ],

        "upload_directory_error" => "アップロードディレクトリが存在しません。",
        "upload_failed"          => ":fieldをアップロードできませんでした。",
    ],

    "protection_warning"        => "アカウントを保護するためにパスワードを設定してください。 <a href=\":link\">設定する</a>",
    "no_profile_modules_info"   => "Flute にプロファイル モジュールがインストールされていません。<a href=\":link\">マーケットプレイスで見る</a>",
    "was_online"                => "オンラインになりました :date",
    "view"                      => "プロフィールを見る",
    "social_deleted"            => "ソーシャルネットワークのリンクを解除しました！",
    "member_since"              => ":date からのメンバー",
    "hidden_warning"            => "あなたのプロフィールは他のユーザーから非表示になっています。",
    "profile_hidden"            => "このプロファイルは他のユーザーから非表示になっています。",
];
