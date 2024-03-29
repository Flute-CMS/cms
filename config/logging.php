<?php return [
    'loggers' => [
        'flute' => [
            'path' => BASE_PATH . "storage/logs/flute.log",
            'level' => Monolog\Logger::DEBUG,
        ],
        'modules' => [
            'path' => BASE_PATH . "storage/logs/modules.log",
            'level' => Monolog\Logger::INFO,
        ],
        'templates' => [
            'path' => BASE_PATH . "storage/logs/templates.log",
            'level' => Monolog\Logger::INFO,
        ],
        'database' => [
            'path' => BASE_PATH . "storage/logs/database.log",
            'level' => Monolog\Logger::INFO,
        ],
    ],
];