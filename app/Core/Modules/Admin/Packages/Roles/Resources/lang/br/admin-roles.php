<?php

return [
    'title' => [
        'roles' => 'Cargos',
        'roles_description' => 'Gerenciamento de cargos de usuário. O cargo mais alto tem a maior prioridade.',
    ],
    'breadcrumbs' => [
        'roles' => 'Cargos',
    ],
    'buttons' => [
        'create' => 'Criar Cargo',
        'edit' => 'Editar',
        'delete' => 'Excluir',
        'save' => 'Salvar',
        'update' => 'Atualizar',
    ],
    'table' => [
        'role_name' => 'Nome do Cargo',
        'actions' => 'Ações',
    ],
    'modal' => [
        'create' => [
            'title' => 'Criar Cargo',
            'submit' => 'Criar',
        ],
        'edit' => [
            'title' => 'Editar Cargo',
            'submit' => 'Atualizar',
        ],
        'delete' => [
            'title' => 'Excluir Cargo',
            'confirm' => 'Tem certeza de que deseja excluir este cargo?',
        ],
    ],
    'fields' => [
        'name' => [
            'label' => 'Nome da Cargo',
            'placeholder' => 'Digite o nome do cargo',
            'help' => 'Um nome único para o cargo',
        ],
        'color' => [
            'label' => 'Cor',
            'help' => 'Cor associada ao cargo',
        ],
        'permissions' => [
            'label' => 'Permissões',
            'help' => 'Selecione as permissões para este cargo',
        ],
        'icon' => [
            'label' => 'Ícone',
            'placeholder' => 'ph.regular... ou <svg...',
            'help' => 'Ícone associado ao cargo',
        ],
    ],
    'messages' => [
        'created' => 'Cargo criado com sucesso.',
        'updated' => 'Cargo atualizado com sucesso.',
        'deleted' => 'Cargo excluído com sucesso.',
        'not_found' => 'Cargo não encontrado ou você não tem permissão para editá-lo.',
        'invalid_sort' => 'Dados de ordenação inválidos.',
        'no_permissions' => 'Por favor, selecione ao menos uma permissão.',
    ],
];
