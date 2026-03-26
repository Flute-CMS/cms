<?php

return [
    'vars' => [
        'name' => 'User name',
        'ip' => 'IP address',
        'device' => 'Device / browser',
        'time' => 'Date and time',
        'amount' => 'Amount',
        'balance' => 'Current balance',
        'gateway' => 'Payment method',
        'transaction_id' => 'Transaction ID',
    ],

    'welcome' => [
        'title' => 'Welcome, {name}!',
        'content' => 'Thank you for registering. We are glad to see you!',
    ],

    'new_device_login' => [
        'title' => 'New device sign-in',
        'content' => 'A sign-in from a new device was detected: {device} (IP: {ip}) at {time}. If this was not you, change your password immediately.',
    ],

    'password_changed' => [
        'title' => 'Password changed',
        'content' => 'Your password was changed at {time}. If you did not make this change, contact support immediately.',
    ],

    'payment_success' => [
        'title' => 'Payment successful',
        'content' => 'Your payment of {amount} via {gateway} has been processed. Transaction: {transaction_id}.',
        'view_history' => 'Payment history',
    ],

    'balance_topup' => [
        'title' => 'Balance topped up',
        'content' => 'Your balance has been topped up by {amount}. Current balance: {balance}.',
    ],

    'invoice_created' => [
        'title' => 'Invoice created',
        'content' => 'An invoice for {amount} has been created via {gateway}. Complete the payment to top up your balance.',
        'pay_now' => 'Pay now',
    ],

    'email_verified' => [
        'title' => 'Email confirmed',
        'content' => 'Your email address has been successfully verified. All features are now available.',
    ],
];
