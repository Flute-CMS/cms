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
            'popover' => 'Three-letter code per ISO 4217. Used for payment gateway integration and automatic rate fetching.',
        ],
        'minimum_value' => [
            'label' => 'Minimum Amount',
            'placeholder' => 'Enter minimum amount',
            'help' => 'Minimum top-up amount for this currency',
            'popover' => 'Users will not be able to top up less than this amount. Set to 0 if no limit is needed.',
        ],
        'rate' => [
            'label' => 'Rate',
            'placeholder' => 'Enter currency rate',
            'help' => 'Rate relative to the base currency (1 = base)',
            'help_auto' => 'Rate is updated automatically. Manual value will be overwritten on the next update.',
            'popover' => 'Conversion coefficient relative to the base currency. For example, if base is USD and EUR rate = 0.92, then 1 USD = 0.92 EUR.',
        ],
        'rate_mode' => [
            'label' => 'Rate method',
            'popover' => 'Manual — you set the rate yourself. Automatic — the rate is fetched from a public API when you click "Update Rates".',
            'manual' => 'Manual',
            'auto' => 'Automatic',
        ],
        'auto_rate' => [
            'badge' => 'auto',
        ],
        'rate_markup' => [
            'label' => 'Rate markup (%)',
            'placeholder' => '0',
            'help' => 'Markup percentage on top of the fetched rate (0 = no markup)',
            'popover' => 'Markup is added to the rate fetched from the API. For example, with a rate of 90 and 5% markup, the final rate will be 94.5.',
        ],
        'preset_amounts' => [
            'label' => 'Quick amounts',
            'placeholder' => '100, 500, 1000, 5000',
            'help' => 'Preset amounts for quick selection on the top-up page (comma-separated)',
            'popover' => 'These amounts are shown as quick-select buttons on the top-up page. Leave empty if not needed.',
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
        'no_permission' => [
            'manage' => 'You do not have permission to manage currencies.',
            'delete' => 'You do not have permission to delete currencies.',
        ],
        'invalid_payment_gateways' => 'One or more selected payment gateways are invalid.',
        'no_auto_currencies' => 'No currencies with automatic rate enabled.',
        'rates_fetch_error' => 'Failed to fetch exchange rates. Check API availability.',
        'rates_updated' => 'Rates updated for :count currency(ies).',
    ],

    'empty' => [
        'title' => 'No currencies yet',
        'sub' => 'Create your first currency to start accepting payments',
    ],

    'confirms' => [
        'delete_currency' => 'Are you sure you want to delete this currency? This action cannot be undone.',
        'set_default' => 'Are you sure you want to set this currency as default? All rates will be recalculated.',
        'update_rates' => 'Update rates for all currencies with automatic rate enabled?',
    ],
];
