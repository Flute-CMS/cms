<?php

return [
    // Enable or disable rate limiting globally
    'enabled' => true,

    // Default limiter config
    'policy' => 'fixed_window', // fixed_window | sliding_window | token_bucket
    'limit' => 60,              // number of requests allowed per interval
    'interval' => '1 minute',   // Symfony DateInterval string (e.g., '1 minute', '10 seconds')

    // Default keying strategy: ip | user | ip_user
    'by' => 'ip',

    // Whether to attach X-RateLimit-* headers
    'headers' => true,

    // Optional key prefix for storage
    'key_prefix' => 'rl:',

    'hash_keys' => true, // whether to hash subjects (IP/user) for privacy

    // For token_bucket
    'rate' => [
        'amount' => 1,
    ],
];


