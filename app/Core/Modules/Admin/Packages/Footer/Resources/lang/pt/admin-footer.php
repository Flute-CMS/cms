<?php

return [
    'title'       => 'Rodapé',
    'description' => 'Gerenciar itens do rodapé e links sociais',
    'tabs' => [
        'main_elements' => 'Itens Principais',
        'social'        => 'Redes Sociais',
    ],
    'table' => [
        'title'   => 'Título',
        'icon'    => 'Ícone',
        'url'     => 'URL',
        'actions' => 'Ações',
    ],
    'sections' => [
        'main_links' => [
            'title'       => 'Links Principais',
            'description' => 'Esta página lista todos os itens de rodapé criados no Flute',
        ],
        'social_links' => [
            'title'       => 'Links Sociais do Rodapé',
            'description' => 'Esta página lista todas as redes sociais exibidas no rodapé do site',
        ],
    ],
    'buttons' => [
        'create' => 'Criar',
        'edit'   => 'Editar',
        'delete' => 'Excluir',
    ],
    'modal' => [
        'footer_item' => [
            'create_title' => 'Create Footer Item',
            'edit_title'   => 'Edit Footer Item',
            'fields' => [
                'title' => [
                    'label'       => 'Título',
                    'placeholder' => 'Enter item title',
                    'help'        => 'Footer item title',
                ],
                'url' => [
                    'label'       => 'URL',
                    'placeholder' => 'Enter URL (e.g., /contact)',
                    'help'        => 'Link address. Leave empty if item has children.',
                ],
                'new_tab' => [
                    'label' => 'Abrir em uma nova aba',
                    'help'  => 'Works only if URL is set',
                ],
            ],
        ],
        'social' => [
            'create_title' => 'Create Social Network',
            'edit_title'   => 'Edit Social Network',
            'fields' => [
                'name' => [
                    'label'       => 'Nome',
                    'placeholder' => 'Enter social network name',
                    'help'        => 'Social network name (e.g., Discord)',
                ],
                'icon' => [
                    'label'       => 'Ícone',
                    'placeholder' => 'Enter icon (e.g., ph.regular.discord-logo)',
                    'help'        => 'Icon identifier, e.g. "ph.bold.discord-logo-bold"',
                ],
                'url' => [
                    'label'       => 'URL',
                    'placeholder' => 'Digite a URL (ex: https://discord.gg/yourpage)',
                    'help'        => 'Link para sua página de rede social',
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
        'item_created'      => 'Item de rodapé criado com sucesso.',
        'item_updated'      => 'Item de rodapé atualizado com sucesso.',
        'item_deleted'      => 'Item de rodapé excluído com sucesso.',
        'item_not_found'    => 'Item de rodapé não encontrado.',
        'social_created'    => 'Social network created successfully.',
        'social_updated'    => 'Social network updated successfully.',
        'social_deleted'    => 'Social network deleted successfully.',
        'social_not_found'  => 'Social network not found.',
    ],
];
