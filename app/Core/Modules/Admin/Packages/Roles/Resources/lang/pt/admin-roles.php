<?php

return [
    'title' => [
        'roles'             => 'Cargos',
        'roles_description' => 'User roles management. The highest role has the highest priority.',
    ],
    'breadcrumbs' => [
        'roles' => 'Cargos',
    ],
    'buttons' => [
        'create' => 'Criar Cargo',
        'edit'   => 'Editar',
        'delete' => 'Excluir',
        'save'   => 'Salvar',
        'update' => 'Atualizar',
    ],
    'table' => [
        'role_name' => 'Nome do Cargo',
        'actions'   => 'Ações',
    ],
    'modal' => [
        'create' => [
            'title'  => 'Criar Cargo',
            'submit' => 'Criar',
        ],
        'edit' => [
            'title'  => 'Editar Cargo',
            'submit' => 'Atualizar',
        ],
        'delete' => [
            'title'   => 'Excluir Cargo',
            'confirm' => 'Tem certeza que deseja excluir este cargo?',
        ],
    ],
    'fields' => [
        'name' => [
            'label'       => 'Nome do Cargo',
            'placeholder' => 'Digite o nome do cargo',
            'help'        => 'Um nome exclusivo para o cargo',
        ],
        'color' => [
            'label' => 'Cor',
            'help'  => 'Cor associada ao cargo',
        ],
        'permissions' => [
            'label' => 'Permissões',
            'help'  => 'Selecione as permissões para este cargo',
        ],
    ],
    'messages' => [
        'created'        => 'Cargo criado com sucesso.',
        'updated'        => 'Cargo atualizado com sucesso.',
        'deleted'        => 'Cargo excluído com sucesso.',
        'not_found'      => 'Cargo não encontrado ou você não tem permissão para editá-lo.',
        'invalid_sort'   => 'Ordenar dados inválidos.',
        'no_permissions' => 'Selecione pelo menos uma permissão.',
    ],
];
