<?php

return [
    'title' => 'イベントログ',
    'description' => 'システムログの表示と管理',

    'labels' => [
        'select_file' => 'ログファイルを選択',
        'log_file' => 'ファイル',
        'size' => 'サイズ',
        'modified' => '変更日時',
        'level' => 'レベル',
        'date' => '日付',
        'channel' => 'チャンネル',
        'message' => 'メッセージ',
        'details' => '詳細',
        'filter_by_level' => 'すべてのレベル',
        'no_logs' => 'ログが見つかりません',
        'no_logs_description' => '選択されたフィルターのログエントリが見つかりません',
        'main' => 'メイン',
        'entries' => 'エントリ',
        'entries_loaded' => 'エントリが読み込まれました',
        'context_data' => 'コンテキストデータ',
        'search_placeholder' => 'ログを検索...',
        'of' => '/',
    ],

    'level_labels' => [
        'debug' => 'デバッグ',
        'info' => '情報',
        'notice' => '通知',
        'warning' => '警告',
        'error' => 'エラー',
        'critical' => '重大',
        'alert' => 'アラート',
        'emergency' => '緊急',
    ],

    'refresh' => '更新',
    'download' => '詳細付きでダウンロード',
    'all_levels' => 'すべてのレベル',
    'show_context' => 'コンテキスト',
    'show_more' => 'もっと見る',
    'show_less' => '少なく表示',

    'clear_log' => 'ログをクリア',
    'clear_confirm' => 'このログファイルをクリアしてもよろしいですか？',
    'cleared_success' => 'ログファイルが正常にクリアされました',
    'cleared_error' => 'ログファイルのクリアエラー',

    'export_error' => 'ログファイルのエクスポートエラー',
    'export_success' => 'ログファイルがダウンロード用に準備されました',

    'no_log_selected' => 'ログファイルが選択されていません',
    'auto_refresh_enabled' => '自動更新が有効になりました',
    'auto_refresh_disabled' => '自動更新が無効になりました',
    'load_more' => 'さらにエントリを読み込む',
    'search_logs' => 'ログを検索',
    'page' => 'ページ',
    'previous' => '前へ',
    'next' => '次へ',
];
