<?php

return [
    'title' => 'Navegação',
    'description' => 'Esta página lista todos os itens de navegação criados no Flute',
    'table' => [
        'title' => 'Título',
        'actions' => 'Ações',
    ],
    'buttons' => [
        'create' => 'Criar Item',
        'edit' => 'Editar',
        'delete' => 'Excluir',
    ],
    'modal' => [
        'item' => [
            'create_title' => 'Criar Item de Navegação',
            'edit_title' => 'Editar Item de Navegação',
            'fields' => [
                'title' => [
                    'label' => 'Título',
                    'placeholder' => 'Digite o título do item',
                    'help' => 'Título do item de navegação',
                ],
                'description' => [
                    'label' => 'Descrição',
                    'placeholder' => 'Digite a descrição do item (opcional)',
                    'help' => 'Descrição opcional para o item de navegação',
                ],
                'url' => [
                    'label' => 'URL',
                    'placeholder' => 'Digite a URL (ex.: /home)',
                    'help' => 'Endereço do link. Deixe vazio se o item tiver filhos.',
                ],
                'new_tab' => [
                    'label' => 'Abrir em nova aba',
                    'help' => 'Funciona apenas se a URL estiver definida',
                ],
                'icon' => [
                    'label' => 'Ícone',
                    'placeholder' => 'Digite o ícone (ex.: ph.regular.house)',
                ],
                'visibility_auth' => [
                    'label' => 'Visibilidade',
                    'help' => 'Quem pode ver este item de navegação',
                    'options' => [
                        'all' => 'Todos',
                        'guests' => 'Apenas visitantes',
                        'logged_in' => 'Apenas usuários logados',
                    ],
                ],
                'visibility' => [
                    'label' => 'Tipo de Exibição',
                    'help' => 'Onde este item será exibido',
                    'options' => [
                        'all' => 'Todos',
                        'desktop' => 'Apenas desktop',
                        'mobile' => 'Apenas mobile',
                    ],
                ],
            ],
            'roles' => [
                'title' => 'Cargos',
                'help' => 'Quais cargos podem ver este item. Se nenhuma for selecionada, será visível para todos os usuários',
            ],
        ],
    ],
    'confirms' => [
        'delete_item' => 'Tem certeza de que deseja excluir este item de navegação?',
    ],
    'messages' => [
        'invalid_sort_data' => 'Dados de ordenação inválidos.',
        'item_created' => 'Item de navegação criado com sucesso.',
        'item_updated' => 'Item de navegação atualizado com sucesso.',
        'item_deleted' => 'Item de navegação excluído com sucesso.',
        'item_not_found' => 'Item de navegação não encontrado.',
    ],
];
