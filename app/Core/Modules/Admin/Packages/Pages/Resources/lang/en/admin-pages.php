<?php

return [
    'search_pages' => 'Search Pages',
    'title' => [
        'list' => 'Pages',
        'edit' => 'Edit Page',
        'create' => 'Add Page',
        'description' => 'All pages created in Flute are listed here',
        'main_info' => 'Main Information',
        'actions' => 'Actions',
        'actions_description' => 'Actions on the page',
        'content' => 'Content',
        'blocks' => 'Page Blocks',
        'seo' => 'SEO Settings',
        'permissions' => 'Permissions',
    ],

    'tabs' => [
        'main' => 'Main',
        'blocks' => 'Blocks',
        'permissions' => 'Permissions',
    ],

    'fields' => [
        'route' => [
            'label' => 'Route',
            'placeholder' => 'Enter page route (e.g., /about)',
            'help' => 'URL path for this page',
        ],
        'title' => [
            'label' => 'Title',
            'placeholder' => 'Enter page title',
            'help' => 'Page title displayed in browser and search engines',
        ],
        'description' => [
            'label' => 'Description',
            'placeholder' => 'Enter page description',
            'help' => 'Meta description for search engines',
        ],
        'keywords' => [
            'label' => 'Keywords',
            'placeholder' => 'Enter keywords separated by commas',
            'help' => 'Meta keywords for search engines',
        ],
        'robots' => [
            'label' => 'Robots',
            'placeholder' => 'index, follow',
            'help' => 'Instructions for search engine crawlers',
        ],
        'og_image' => [
            'label' => 'OG Image',
            'placeholder' => 'Enter image URL',
            'help' => 'Image for social media sharing',
        ],
        'created_at' => 'Created At',
    ],

    'blocks' => [
        'title' => 'Page Blocks',
        'fields' => [
            'widget' => [
                'label' => 'Widget',
                'placeholder' => 'Select widget',
                'help' => 'Widget type for this block',
            ],
            'gridstack' => [
                'label' => 'Grid Settings',
                'placeholder' => 'Enter grid settings in JSON',
                'help' => 'GridStack positioning settings',
            ],
            'settings' => [
                'label' => 'Block Settings',
                'placeholder' => 'Enter block settings in JSON',
                'help' => 'Widget-specific settings',
            ],
        ],
        'add' => [
            'title' => 'Add Block',
            'button' => 'Add Block',
        ],
        'edit' => [
            'title' => 'Edit Block',
        ],
        'delete' => [
            'confirm' => 'Are you sure you want to delete this block?',
        ],
    ],

    'buttons' => [
        'add' => 'Add',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'actions' => 'Actions',
        'goto' => 'Go to',
    ],

    'messages' => [
        'page_not_found' => 'Page not found.',
        'block_not_found' => 'Block not found.',
        'save_success' => 'Page saved successfully.',
        'delete_success' => 'Page deleted successfully.',
        'block_add_success' => 'Block added successfully.',
        'block_update_success' => 'Block updated successfully.',
        'block_delete_success' => 'Block deleted successfully.',
        'save_page_first' => 'Please save the page first.',
        'invalid_json' => 'Invalid JSON format.',
        'page_deleted' => 'Page removed successfully.',
        'page_updated' => 'Page updated successfully.',
        'page_created' => 'Page created successfully.',
        'route_exists' => 'A page with this route already exists.',
        'invalid_route' => 'Route must start with / and contain only valid URL characters.',
        'no_permission.manage' => 'You do not have permission to manage pages.',
        'no_permission.delete' => 'You do not have permission to delete pages.',
    ],

    'confirms' => [
        'delete_page' => 'Are you sure you want to delete this page? This action cannot be undone.',
    ],
];
