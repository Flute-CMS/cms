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
        'refresh' => 'Refresh Themes List',
        'details' => 'Detalhes',
        'install' => 'Instalar',
    ],
    'status' => [
        'active'       => 'Ativo',
        'inactive'     => 'Inativo',
        'not_installed'=> 'Não Instalado',
    ],
    'confirms' => [
        'delete' => 'Are you sure you want to delete this theme?',
        'install'=> 'Are you sure you want to install this theme?',
    ],
    'messages' => [
        'save_success'   => 'Theme saved successfully.',
        'save_error'     => 'Error saving theme: :message',
        'delete_success' => 'Theme deleted successfully.',
        'delete_error'   => 'Error deleting theme: :message',
        'toggle_success' => 'Theme status changed successfully.',
        'toggle_error'   => 'Error changing theme status.',
        'not_found'      => 'Theme not found.',
        'refresh_success'=> 'Themes list refreshed successfully.',
        'install_success'=> 'Theme installed successfully.',
        'install_error'  => 'Error installing theme: :message',
        'enable_success' => 'Theme enabled successfully.',
        'enable_error'   => 'Error enabling theme: :message',
        'disable_success'=> 'Theme disabled successfully.',
        'disable_error'  => 'Error disabling theme: :message',
    ],
];
