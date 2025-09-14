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
        'footer_item' => [
            'create_title' => 'Criar Item de Rodapé',
            'edit_title' => 'Editar Item de Rodapé',
            'fields' => [
                'title' => [
                    'label' => 'Título',
                    'placeholder' => 'Digite o título do item',
                    'help' => 'Título do item do rodapé',
                ],
                'icon' => [
                    'label' => 'Ícone',
                    'placeholder' => 'Digite o ícone (ex.: ph.regular.home)',
                    'help' => 'Identificador do ícone (opcional)',
                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'Digite a URL (ex.: /contato)',
                    'help' => 'Endereço do link. Deixe vazio se o item tiver filhos.',
                ],
                'new_tab' => [
                    'label' => 'Abrir em nova aba',
                    'help' => 'Funciona apenas se a URL estiver definida',
                ],
            ],
        ],
        'social' => [
            'create_title' => 'Criar Rede Social',
            'edit_title' => 'Editar Rede Social',
            'fields' => [
                'name' => [
                    'label' => 'Nome',
                    'placeholder' => 'Digite o nome da rede social',
                    'help' => 'Nome da rede social (ex.: Discord)',
                ],
                'icon' => [
                    'label' => 'Ícone',
                    'placeholder' => 'Digite o ícone (ex.: ph.regular.discord-logo)',
                    'help' => 'Identificador do ícone, ex.: "ph.bold.discord-logo-bold"',
                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'Digite a URL (ex.: https://discord.gg/suapagina)',
                    'help' => 'Link para a página da sua rede social',
                ],
            ],
        ],
    ],
    'confirms' => [
        'delete_item' => 'Tem certeza de que deseja excluir este item do rodapé?',
        'delete_social' => 'Tem certeza de que deseja excluir esta rede social?',
    ],
    'messages' => [
        'invalid_sort_data' => 'Dados de ordenação inválidos.',
        'item_created' => 'Item de rodapé criado com sucesso.',
        'item_updated' => 'Item de rodapé atualizado com sucesso.',
        'item_deleted' => 'Item de rodapé excluído com sucesso.',
        'item_not_found' => 'Item de rodapé não encontrado.',
        'social_created' => 'Rede social criada com sucesso.',
        'social_updated' => 'Rede social atualizada com sucesso.',
        'social_deleted' => 'Rede social excluída com sucesso.',
        'social_not_found' => 'Rede social não encontrada.',
    ],
];
