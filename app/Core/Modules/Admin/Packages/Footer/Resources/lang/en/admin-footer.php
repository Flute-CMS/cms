<?php

return [
    'title'       => 'Footer',
    'description' => 'Manage footer items and social links',
    'tabs' => [
        'main_elements' => 'Main Items',
        'social'        => 'Social Networks',
    ],
    'table' => [
        'title'   => 'Title',
        'icon'    => 'Icon',
        'url'     => 'URL',
        'actions' => 'Actions',
    ],
    'sections' => [
        'main_links' => [
            'title'       => 'Main Links',
            'description' => 'This page lists all created footer items in Flute',
        ],
        'social_links' => [
            'title'       => 'Footer Social Links',
            'description' => 'This page lists all social networks displayed in the site footer',
        ],
    ],
    'buttons' => [
        'create' => 'Create',
        'edit'   => 'Edit',
        'delete' => 'Delete',
    ],
    'modal' => [
        'footer_item' => [
            'create_title' => 'Create Footer Item',
            'edit_title'   => 'Edit Footer Item',
            'fields' => [
                'title' => [
                    'label'       => 'Title',
                    'placeholder' => 'Enter item title',
                    'help'        => 'Footer item title',
                ],
                'icon' => [
                    'label'       => 'Icon',
                    'placeholder' => 'Enter icon (e.g., ph.regular.home)',
                    'help'        => 'Icon identifier (optional)',
                ],
                'url' => [
                    'label'       => 'URL',
                    'placeholder' => 'Enter URL (e.g., /contact)',
                    'help'        => 'Link address. Leave empty if item has children.',
                ],
                'new_tab' => [
                    'label' => 'Open in new tab',
                    'help'  => 'Works only if URL is set',
                ],
            ],
        ],
        'social' => [
            'create_title' => 'Create Social Network',
            'edit_title'   => 'Edit Social Network',
            'fields' => [
                'name' => [
                    'label'       => 'Name',
                    'placeholder' => 'Enter social network name',
                    'help'        => 'Social network name (e.g., Discord)',
                ],
                'icon' => [
                    'label'       => 'Icon',
                    'placeholder' => 'Enter icon (e.g., ph.regular.discord-logo)',
                    'help'        => 'Icon identifier, e.g. "ph.bold.discord-logo-bold"',
                ],
                'url' => [
                    'label'       => 'URL',
                    'placeholder' => 'Enter URL (e.g., https://discord.gg/yourpage)',
                    'help'        => 'Link to your social network page',
                ],
            ],
        ],
    ],
    'confirms' => [
        'delete_item'   => 'Are you sure you want to delete this footer item?',
        'delete_social' => 'Are you sure you want to delete this social network?',
    ],
    'messages' => [
        'invalid_sort_data' => 'Invalid sort data.',
        'item_created'      => 'Footer item created successfully.',
        'item_updated'      => 'Footer item updated successfully.',
        'item_deleted'      => 'Footer item deleted successfully.',
        'item_not_found'    => 'Footer item not found.',
        'social_created'    => 'Social network created successfully.',
        'social_updated'    => 'Social network updated successfully.',
        'social_deleted'    => 'Social network deleted successfully.',
        'social_not_found'  => 'Social network not found.',
    ],
];
