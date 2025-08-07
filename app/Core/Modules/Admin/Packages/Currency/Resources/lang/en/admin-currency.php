<?php

return [
    'title' => [
        'list' => 'Currencies',
        'edit' => 'Edit Currency',
        'create' => 'Create Currency',
        'description' => 'This page lists all system currencies',
        'main_info' => 'Main Information',
        'actions' => 'Actions',
        'actions_description' => 'Actions on the currency',
    ],

    'fields' => [
        'name' => [
            'label' => 'Name',
            'placeholder' => 'Enter currency name',
        ],
        'code' => [
            'label' => 'Code',
            'placeholder' => 'Enter currency code',
            'help' => 'Unique currency code (e.g.: USD, EUR, RUB)',
        ],
        'minimum_value' => [
            'label' => 'Minimum Amount',
            'placeholder' => 'Enter minimum amount',
            'help' => 'Minimum top-up amount for this currency',
        ],
        'rate' => [
            'label' => 'Rate',
            'placeholder' => 'Enter currency rate',
            'help' => 'Rate relative to the base currency',
        ],
        'enabled' => [
            'label' => 'Enabled',
            'help' => 'An enabled currency is available for use in the system',
        ],
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'default' => 'Default',
    ],

    'buttons' => [
        'add' => 'Add Currency',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'actions' => 'Actions',
        'update_rates' => 'Update Rates',
    ],

    'messages' => [
        'currency_not_found' => 'Currency not found.',
        'save_success' => 'Currency saved successfully.',
        'delete_success' => 'Currency deleted successfully.',
        'update_rates_success' => 'Currency rates updated successfully.',
        'default_currency_delete' => 'Cannot delete the default currency.',
        'no_permission.manage' => 'You do not have permission to manage currencies.',
        'no_permission.delete' => 'You do not have permission to delete currencies.',
    ],

    'confirms' => [
        'delete_currency' => 'Are you sure you want to delete this currency? This action cannot be undone.',
        'set_default' => 'Are you sure you want to set this currency as default? All rates will be recalculated.',
    ],
];
