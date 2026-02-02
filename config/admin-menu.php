<?php

/**
 * Admin Panel Menu Configuration
 *
 * - 'key' => matches package's menu item key
 * - 'section' => section header
 * - 'children' => groups items (1 item = no nesting)
 */

return [
    // Main (no header)
    ['key' => 'dashboard'],

    // Management
    ['section' => 'admin-menu.sections.management'],
    [
        'key' => 'settings-group',
        'title' => 'admin-menu.settings',
        'icon' => 'ph.regular.gear',
        'children' => ['main-settings', 'notifications', 'api-keys', 'socials'],
    ],
    [
        'key' => 'users-group',
        'title' => 'admin-menu.users',
        'icon' => 'ph.regular.users',
        'children' => ['users', 'roles'],
    ],
    [
        'key' => 'content-group',
        'title' => 'admin-menu.content',
        'icon' => 'ph.regular.article',
        'children' => ['pages', 'navigation', 'footer'],
    ],
    ['key' => 'servers'],

    // Finance
    ['section' => 'admin-menu.sections.finance'],
    [
        'key' => 'finance-group',
        'title' => 'admin-menu.finance',
        'icon' => 'ph.regular.wallet',
        'children' => ['gateways', 'invoices', 'promo-codes', 'currencies'],
    ],

    // Extensions
    ['section' => 'admin-menu.sections.extensions'],
    ['key' => 'modules'],
    ['key' => 'themes'],
    ['key' => 'marketplace'],

    // System
    ['section' => 'admin-menu.sections.system'],
    [
        'key' => 'system-group',
        'title' => 'admin-menu.system',
        'icon' => 'ph.regular.info',
        'children' => ['logs', 'updates', 'about'],
    ],
];
