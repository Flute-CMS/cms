<?php

return [
    'title' => [
        'themes'      => 'Themes',
        'description' => 'On this page you can manage themes and their settings',
        'edit'        => 'Edit Theme: :name',
        'create'      => 'Add Theme',
    ],
    'table' => [
        'name'    => 'Name',
        'version' => 'Version',
        'status'  => 'Status',
        'actions' => 'Actions',
    ],
    'fields' => [
        'name' => [
            'label'       => 'Name',
            'placeholder' => 'Enter theme name',
        ],
        'version' => [
            'label'       => 'Version',
            'placeholder' => 'Enter theme version',
        ],
        'enabled' => [
            'label' => 'Enabled',
            'help'  => 'Enable or disable this theme',
        ],
        'description' => [
            'label'       => 'Description',
            'placeholder' => 'Enter theme description',
        ],
        'author' => [
            'label'       => 'Author',
            'placeholder' => 'Enter theme author',
        ],
    ],
    'buttons' => [
        'save'    => 'Save',
        'edit'    => 'Edit',
        'delete'  => 'Delete',
        'enable'  => 'Enable',
        'disable' => 'Disable',
        'refresh' => 'Refresh Themes List',
        'details' => 'Details',
        'install' => 'Install',
    ],
    'status' => [
        'active'       => 'Active',
        'inactive'     => 'Inactive',
        'not_installed'=> 'Not Installed',
    ],
    'confirms' => [
        'delete' => 'Are you sure you want to delete this theme?',
        'install'=> 'Are you sure you want to install this theme?',
    ],
    'messages' => [
        'save_success'   => 'Theme saved successfully.',
        'save_error'     => 'Error saving theme: :message',
        'delete_success' => 'Theme deleted successfully.',
        'delete_error'   => 'Error deleting theme: :message',
        'toggle_success' => 'Theme status changed successfully.',
        'toggle_error'   => 'Error changing theme status.',
        'not_found'      => 'Theme not found.',
        'refresh_success'=> 'Themes list refreshed successfully.',
        'install_success'=> 'Theme installed successfully.',
        'install_error'  => 'Error installing theme: :message',
        'enable_success' => 'Theme enabled successfully.',
        'enable_error'   => 'Error enabling theme: :message',
        'disable_success'=> 'Theme disabled successfully.',
        'disable_error'  => 'Error disabling theme: :message',
    ],
];
