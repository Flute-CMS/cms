<?php

return [
    'title' => 'Event Log',
    'description' => 'View and manage system logs',

    'labels' => [
        'select_file' => 'Select log file',
        'log_file' => 'File',
        'size' => 'Size',
        'modified' => 'Modified',
        'level' => 'Level',
        'date' => 'Date',
        'channel' => 'Channel',
        'message' => 'Message',
        'details' => 'Details',
        'filter_by_level' => 'All Levels',
        'no_logs' => 'No logs found',
        'no_logs_description' => 'No log entries found for the selected filters',
        'main' => 'Main',
        'entries' => 'entries',
        'entries_loaded' => 'entries loaded',
        'context_data' => 'Context Data',
        'search_placeholder' => 'Search logs...',
        'of' => 'of',
    ],

    'level_labels' => [
        'debug' => 'Debug',
        'info' => 'Info',
        'notice' => 'Notice',
        'warning' => 'Warning',
        'error' => 'Error',
        'critical' => 'Critical',
        'alert' => 'Alert',
        'emergency' => 'Emergency',
    ],

    'refresh' => 'Refresh',
    'download' => 'Download with details',
    'all_levels' => 'All Levels',
    'show_context' => 'Context',
    'show_more' => 'Show more',
    'show_less' => 'Show less',

    'clear_log' => 'Clear Log',
    'clear_confirm' => 'Are you sure you want to clear this log file?',
    'cleared_success' => 'Log file cleared successfully',
    'cleared_error' => 'Error clearing log file',

    'export_error' => 'Error exporting log file',
    'export_success' => 'Log file prepared for download',

    'no_log_selected' => 'No log file selected',
    'auto_refresh_enabled' => 'Auto-refresh enabled',
    'auto_refresh_disabled' => 'Auto-refresh disabled',
    'load_more' => 'Load more entries',
    'search_logs' => 'Search logs',
    'page' => 'Page',
    'previous' => 'Previous',
    'next' => 'Next',
];
