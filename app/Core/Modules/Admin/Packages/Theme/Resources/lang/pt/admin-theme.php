<?php

return [
    'title' => [
        'themes'      => 'Temas',
        'description' => 'Nesta página você pode gerenciar temas e suas configurações',
        'edit'        => 'Editar Tema: :name',
        'create'      => 'Adicionar Tema',
    ],
    'table' => [
        'name'    => 'Nome',
        'version' => 'Versão',
        'status'  => 'Status',
        'actions' => 'Ações',
    ],
    'fields' => [
        'name' => [
            'label'       => 'Nome',
            'placeholder' => 'Digite o nome do tema',
        ],
        'version' => [
            'label'       => 'Versão',
            'placeholder' => 'Inserir versão do tema',
        ],
        'enabled' => [
            'label' => 'Ativado',
            'help'  => 'Ativar ou desativar este tema',
        ],
        'description' => [
            'label'       => 'Descrição',
            'placeholder' => 'Inserir descrição do tema',
        ],
    ],
    'buttons' => [
        'save'    => 'Salvar',
        'edit'    => 'Editar',
        'delete'  => 'Excluir',
        'enable'  => 'Ativar',
        'disable' => 'Desativar',
        'refresh' => 'Atualizar lista de temas',
        'details' => 'Detalhes',
        'install' => 'Instalar',
    ],
    'status' => [
        'active'       => 'Ativo',
        'inactive'     => 'Inativo',
        'not_installed'=> 'Não Instalado',
    ],
    'confirms' => [    ],
    'messages' => [        'refresh_success'=> 'Lista de temas atualizada com sucesso.',    ],
];
