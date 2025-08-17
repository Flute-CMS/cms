<?php

return [
    'title' => 'Rodapé',
    'description' => 'Gerenciar itens do rodapé e links sociais',
    'tabs' => [
        'main_elements' => 'Itens Principais',
        'social' => 'Redes Sociais',
    ],
    'table' => [
        'title' => 'Título',
        'icon' => 'Ícone',
        'url' => 'URL',
        'actions' => 'Ações',
    ],
    'sections' => [
        'main_links' => [
            'title' => 'Links Principais',
            'description' => 'Esta página lista todos os itens de rodapé criados no Flute',
        ],
        'social_links' => [
            'title' => 'Links Sociais do Rodapé',
            'description' => 'Esta página lista todas as redes sociais exibidas no rodapé do site',
        ],
    ],
    'buttons' => [
        'create' => 'Criar',
        'edit' => 'Editar',
        'delete' => 'Excluir',
    ],
    'modal' => [
        'footer_item' => [            'fields' => [
                'title' => [
                    'label' => 'Título',                ],
                'url' => [
                    'label' => 'URL',                ],
                'new_tab' => [
                    'label' => 'Abrir em uma nova aba',                ],
            ],
        ],
        'social' => [            'fields' => [
                'name' => [
                    'label' => 'Nome',                ],
                'icon' => [
                    'label' => 'Ícone',                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'Digite a URL (ex: https://discord.gg/yourpage)',
                    'help' => 'Link para sua página de rede social',
                ],
            ],
        ],
    ],
    'confirms' => [    ],
    'messages' => [        'item_created' => 'Item de rodapé criado com sucesso.',
        'item_updated' => 'Item de rodapé atualizado com sucesso.',
        'item_deleted' => 'Item de rodapé excluído com sucesso.',
        'item_not_found' => 'Item de rodapé não encontrado.',    ],
];
