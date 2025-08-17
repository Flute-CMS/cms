<?php

return [
    'title' => [
        'roles' => 'Roles',
        'roles_description' => 'User roles management. The highest role has the highest priority.',
    ],
    'breadcrumbs' => [
        'roles' => 'Roles',
    ],
    'buttons' => [
        'create' => 'Create Role',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'update' => 'Update',
    ],
    'table' => [
        'role_name' => 'Role Name',
        'actions' => 'Actions',
    ],
    'modal' => [
        'create' => [
            'title' => 'Create Role',
            'submit' => 'Create',
        ],
        'edit' => [
            'title' => 'Edit Role',
            'submit' => 'Update',
        ],
        'delete' => [
            'title' => 'Delete Role',
            'confirm' => 'Are you sure you want to delete this role?',
        ],
    ],
    'fields' => [
        'name' => [
            'label' => 'Role Name',
            'placeholder' => 'Enter role name',
            'help' => 'A unique name for the role',
        ],
        'color' => [
            'label' => 'Color',
            'help' => 'Color associated with the role',
        ],
        'permissions' => [
            'label' => 'Permissions',
            'help' => 'Select permissions for this role',
        ],
        'icon' => [
            'label' => 'Icon',
            'placeholder' => 'ph.regular... or <svg...',
            'help' => 'Icon associated with the role',
        ],
    ],
    'messages' => [
        'created' => 'Role created successfully.',
        'updated' => 'Role updated successfully.',
        'deleted' => 'Role deleted successfully.',
        'not_found' => 'Role not found or you do not have permission to edit it.',
        'invalid_sort' => 'Invalid sort data.',
        'no_permissions' => 'Please select at least one permission.',
    ],
];
