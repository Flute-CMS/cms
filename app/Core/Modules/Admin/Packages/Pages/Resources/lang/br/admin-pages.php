<?php

return [
    'search_pages' => 'Pesquisar Páginas',
    'title' => [
        'list' => 'Páginas',
        'edit' => 'Editar Página',
        'create' => 'Adicionar Página',
        'description' => 'Todas as páginas criadas no Flute estão listadas aqui',
        'main_info' => 'Informações Principais',
        'actions' => 'Ações',
        'actions_description' => 'Ações na página',
        'content' => 'Conteúdo',
        'blocks' => 'Blocos da Página',
        'seo' => 'Configurações de SEO',
        'permissions' => 'Permissões',
    ],

    'tabs' => [
        'main' => 'Principal',
        'blocks' => 'Blocos',
        'permissions' => 'Permissões',
    ],

    'fields' => [
        'route' => [
            'label' => 'Rota',
            'placeholder' => 'Digite a rota da página (ex.: /sobre)',
            'help' => 'Caminho da URL para esta página',
        ],
        'title' => [
            'label' => 'Título',
            'placeholder' => 'Digite o título da página',
            'help' => 'Título da página exibido no navegador e nos mecanismos de busca',
        ],
        'description' => [
            'label' => 'Descrição',
            'placeholder' => 'Digite a descrição da página',
            'help' => 'Meta descrição para mecanismos de busca',
        ],
        'keywords' => [
            'label' => 'Palavras-chave',
            'placeholder' => 'Digite palavras-chave separadas por vírgulas',
            'help' => 'Meta palavras-chave para mecanismos de busca',
        ],
        'robots' => [
            'label' => 'Robots',
            'placeholder' => 'index, follow',
            'help' => 'Instruções para rastreadores de mecanismos de busca',
        ],
        'og_image' => [
            'label' => 'Imagem OG',
            'placeholder' => 'Digite a URL da imagem',
            'help' => 'Imagem para compartilhamento em redes sociais',
        ],
        'created_at' => 'Criado em',
    ],

    'blocks' => [
        'title' => 'Blocos da Página',
        'fields' => [
            'widget' => [
                'label' => 'Widget',
                'placeholder' => 'Selecione o widget',
                'help' => 'Tipo de widget para este bloco',
            ],
            'gridstack' => [
                'label' => 'Configurações de Grade',
                'placeholder' => 'Digite as configurações da grade em JSON',
                'help' => 'Configurações de posicionamento do GridStack',
            ],
            'settings' => [
                'label' => 'Configurações do Bloco',
                'placeholder' => 'Digite as configurações do bloco em JSON',
                'help' => 'Configurações específicas do widget',
            ],
        ],
        'add' => [
            'title' => 'Adicionar Bloco',
            'button' => 'Adicionar Bloco',
        ],
        'edit' => [
            'title' => 'Editar Bloco',
        ],
        'delete' => [
            'confirm' => 'Tem certeza de que deseja excluir este bloco?',
        ],
    ],

    'buttons' => [
        'add' => 'Adicionar',
        'save' => 'Salvar',
        'cancel' => 'Cancelar',
        'delete' => 'Excluir',
        'edit' => 'Editar',
        'actions' => 'Ações',
        'goto' => 'Ir para',
    ],

    'messages' => [
        'page_not_found' => 'Página não encontrada.',
        'block_not_found' => 'Bloco não encontrado.',
        'save_success' => 'Página salva com sucesso.',
        'delete_success' => 'Página excluída com sucesso.',
        'block_add_success' => 'Bloco adicionado com sucesso.',
        'block_update_success' => 'Bloco atualizado com sucesso.',
        'block_delete_success' => 'Bloco excluído com sucesso.',
        'save_page_first' => 'Por favor, salve a página primeiro.',
        'invalid_json' => 'Formato JSON inválido.',
        'page_deleted' => 'Página removida com sucesso.',
        'page_updated' => 'Página atualizada com sucesso.',
        'page_created' => 'Página criada com sucesso.',
        'route_exists' => 'Já existe uma página com esta rota.',
        'invalid_route' => 'A rota deve começar com / e conter apenas caracteres válidos de URL.',
        'no_permission.manage' => 'Você não tem permissão para gerenciar páginas.',
        'no_permission.delete' => 'Você não tem permissão para excluir páginas.',
    ],

    'confirms' => [
        'delete_page' => 'Tem certeza de que deseja excluir esta página? Esta ação não pode ser desfeita.',
    ],
];
