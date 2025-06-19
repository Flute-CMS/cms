<?php

return [
    'title'       => 'Navigation',
    'description' => 'This page lists all navigation items created in Flute',
    'table' => [
        'title'   => 'Title',
        'actions' => 'Ações',
    ],
    'buttons' => [
        'create' => 'Create Item',
        'edit'   => 'Edit',
        'delete' => 'Delete',
    ],
    'modal' => [
        'item' => [
            'create_title' => 'Create Navigation Item',
            'edit_title'   => 'Edit Navigation Item',
            'fields' => [
                'title' => [
                    'label'       => 'Título',
                    'placeholder' => 'Inserir título do item',
                    'help'        => 'Navigation item title',
                ],
                'url' => [
                    'label'       => 'URL',
                    'placeholder' => 'Enter URL (e.g., /home)',
                    'help'        => 'Link address. Leave empty if item has children.',
                ],
                'new_tab' => [
                    'label' => 'Open in new tab',
                    'help'  => 'Works only if URL is set',
                ],
                'icon' => [
                    'label'       => 'Ícone',
                    'placeholder' => 'Enter icon (e.g., ph.regular.house)',
                ],
                'visibility_auth' => [
                    'label'       => 'Visibility',
                    'help'        => 'Who can see this navigation item',
                    'options'     => [
                        'all'       => 'All',
                        'guests'    => 'Guests only',
                        'logged_in' => 'Logged in only',
                    ],
                ],
                'visibility' => [
                    'label'   => 'Display Type',
                    'help'    => 'Where this item will be displayed',
                    'options' => [
                        'all'     => 'All',
                        'desktop' => 'Desktop only',
                        'mobile'  => 'Mobile only',
                    ],
                ],
            ],
            'roles' => [
                'title' => 'Cargos',
                'help'  => 'Which roles can see this item. If none selected, visible to all users',
            ],
        ],
    ],
    'confirms' => [
        'delete_item' => 'Are you sure you want to delete this navigation item?',
    ],
    'messages' => [
        'invalid_sort_data' => 'Invalid sort data.',
        'item_created'      => 'Navigation item created successfully.',
        'item_updated'      => 'Navigation item updated successfully.',
        'item_deleted'      => 'Navigation item deleted successfully.',
        'item_not_found'    => 'Navigation item not found.',
    ],
];
