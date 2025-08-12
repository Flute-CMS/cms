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
            'create_title' => 'Criar Item de Rodapé',
            'edit_title'   => 'Editar item de rodapé',
            'fields' => [
                'title' => [
                    'label'       => 'Título',
                    'placeholder' => 'Inserir título do item',
                    'help'        => 'Título do item do rodapé',
                ],
                'url' => [
                    'label'       => 'URL',
                    'placeholder' => 'Informe a URL (ex.: /contact)',
                    'help'        => 'Endereço do link. Deixe em branco se o item tiver filhos.',
                ],
                'new_tab' => [
                    'label' => 'Abrir em uma nova aba',
                    'help'  => 'Funciona somente se o URL estiver definido',
                ],
            ],
        ],
        'social' => [
            'create_title' => 'Criar Rede Social',
            'edit_title'   => 'Editar Rede Social',
            'fields' => [
                'name' => [
                    'label'       => 'Nome',
                    'placeholder' => 'Insira o nome da rede social',
                    'help'        => 'Nome da rede social (ex.: Discord)',
                ],
                'icon' => [
                    'label'       => 'Ícone',
                    'placeholder' => 'Ícone de entrada (ex, ph.regular.discord-logo)',
                    'help'        => 'Identificador de ícone, ex, "ph.bold.discord-logo-bold"',
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
        'delete_item'   => 'Tem certeza de que deseja excluir este item de rodapé?',
        'delete_social' => 'Tem certeza de que deseja excluir esta rede social?',
    ],
    'messages' => [
        'invalid_sort_data' => 'Ordenar dados inválidos.',
        'item_created'      => 'Item de rodapé criado com sucesso.',
        'item_updated'      => 'Item de rodapé atualizado com sucesso.',
        'item_deleted'      => 'Item de rodapé excluído com sucesso.',
        'item_not_found'    => 'Item de rodapé não encontrado.',
        'social_created'    => 'Rede social criada com sucesso.',
        'social_updated'    => 'Rede social atualizada com sucesso.',
        'social_deleted'    => 'Rede social excluída com sucesso.',
        'social_not_found'  => 'Rede social não encontrada.',
    ],
];
