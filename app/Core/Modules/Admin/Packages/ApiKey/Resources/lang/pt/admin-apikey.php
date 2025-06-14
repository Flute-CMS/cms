<?php

return [
    'title' => [
        'list'        => 'API Keys',
        'description' => 'Manage API keys for external access',
        'create'      => 'Create API Key',
        'edit'        => 'Edit API Key',
    ],
    'fields' => [
        'key' => [
            'label'       => 'API Key',
            'placeholder' => 'Enter API key',
            'help'        => 'This key will be used for API authentication',
        ],
        'name' => [
            'label'       => 'Name',
            'placeholder' => 'Enter key name',
            'help'        => 'You can use this name to identify the key',
        ],
        'permissions' => [
            'label' => 'Permissions',
        ],
        'created_at'   => 'Created At',
        'last_used_at' => 'Last Used At',
        'never'        => 'Never',
    ],
    'buttons' => [
        'actions' => 'Actions',
        'add'     => 'Add Key',
        'save'    => 'Save',
        'edit'    => 'Edit',
        'delete'  => 'Delete',
    ],
    'confirms' => [
        'delete_key' => 'Are you sure you want to delete this API key?',
    ],
    'messages' => [
        'save_success'    => 'API key saved successfully.',
        'key_not_found'   => 'API key not found.',
        'no_permissions'  => 'Please select at least one permission.',
        'update_success'  => 'API key updated successfully.',
        'update_error'    => 'Error updating API key: :message',
        'delete_success'  => 'API key deleted successfully.',
        'delete_error'    => 'Error deleting API key: :message',
    ],
];
