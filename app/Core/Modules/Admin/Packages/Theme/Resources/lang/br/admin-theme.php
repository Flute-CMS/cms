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
    'confirms' => [
        'delete' => 'Tem certeza de que deseja excluir este tema?',
        'install'=> 'Tem certeza de que deseja instalar este tema?',
    ],
    'messages' => [
        'save_success'   => 'Tema salvo com sucesso.',
        'save_error'     => 'Erro ao salvar tema: :message',
        'delete_success' => 'Tema excluído com sucesso.',
        'delete_error'   => 'Erro ao deletar tema: :message',
        'toggle_success' => 'Status do tema alterado com sucesso.',
        'toggle_error'   => 'Erro ao alterar o status do tema.',
        'not_found'      => 'Tema não encontrado.',
        'refresh_success'=> 'Lista de temas atualizada com sucesso.',
        'install_success'=> 'Tema instalado com sucesso.',
        'install_error'  => 'Erro ao instalar o tema: :message',
        'enable_success' => 'Tema ativado com sucesso.',
        'enable_error'   => 'Erro ao ativar tema: :message',
        'disable_success'=> 'Tema desativado com sucesso.',
        'disable_error'  => 'Erro ao desativar tema: :message',
    ],
];
