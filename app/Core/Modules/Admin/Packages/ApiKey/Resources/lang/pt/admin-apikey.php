<?php

return [
    'title' => [
        'list' => 'Chaves API',
        'description' => 'Gerenciar chaves de API para acesso externo',    ],
    'fields' => [
        'key' => [
            'label' => 'Chave API',
            'placeholder' => 'Insira a Chave API',        ],
        'name' => [
            'label' => 'Nome',        ],
        'permissions' => [
            'label' => 'Permissões',
        ],        'never' => 'Nunca',
    ],
    'buttons' => [
        'actions' => 'Ações',        'save' => 'Salvar',
        'edit' => 'Editar',
        'delete' => 'Excluir',
    ],
    'confirms' => [    ],
    'messages' => [    ],

    'info_alert' => [
        'title' => 'Módulo API necessário',
        'description' => 'As chaves API permitem autenticar solicitações, mas o módulo API deve ser instalado do marketplace para que a API funcione.',
        'install_module' => 'Instalar módulo',
        'documentation' => 'Documentação',
    ],
];
