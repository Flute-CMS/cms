<?php

return [
    'title' => [
        'list'        => 'Chaves API',
        'description' => 'Gerenciar chaves de API para acesso externo',
        'create'      => 'Criar Chave API',
        'edit'        => 'Editar Chave API',
    ],
    'fields' => [
        'key' => [
            'label'       => 'Chave API',
            'placeholder' => 'Insira a Chave API',
            'help'        => 'Esta chave será usada para autenticação de API',
        ],
        'name' => [
            'label'       => 'Nome',
            'placeholder' => 'Digite o nome da chave',
            'help'        => 'Você pode usar este nome para identificar a chave',
        ],
        'permissions' => [
            'label' => 'Permissões',
        ],
        'created_at'   => 'Criado em',
        'last_used_at' => 'Usado pela última vez em',
        'never'        => 'Nunca',
    ],
    'buttons' => [
        'actions' => 'Ações',
        'add'     => 'Adicionar Chave',
        'save'    => 'Salvar',
        'edit'    => 'Editar',
        'delete'  => 'Excluir',
    ],
    'confirms' => [
        'delete_key' => 'Tem certeza que deseja excluir esta chave API?',
    ],
    'messages' => [
        'save_success'    => 'Chave API salva com sucesso.',
        'key_not_found'   => 'Chave API não encontrada.',
        'no_permissions'  => 'Selecione pelo menos uma permissão.',
        'update_success'  => 'Chave API atualizada com sucesso.',
        'update_error'    => 'Erro ao atualizar chave API: :message',
        'delete_success'  => 'Chave API excluída com sucesso.',
        'delete_error'    => 'Erro ao excluir chave API: :message',
    ],
];
