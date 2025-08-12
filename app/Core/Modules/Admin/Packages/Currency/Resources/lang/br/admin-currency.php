<?php

return [
    'title' => [
        'list'               => 'Moedas',
        'edit'               => 'Editar Moeda',
        'create'             => 'Criar Moeda',
        'description'        => 'Esta página lista todas as moedas do sistema',
        'main_info'          => 'Informações Principais',
        'actions'            => 'Ações',
        'actions_description'=> 'Ações na moeda',
    ],

    'fields' => [
        'name' => [
            'label'       => 'Nome',
            'placeholder' => 'Insira o nome da moeda',
        ],
        'code' => [
            'label'       => 'Código',
            'placeholder' => 'Insira o código da moeda',
            'help'        => 'Código único da moeda (ex.: USD, EUR, RUB)',
        ],
        'minimum_value' => [
            'label'       => 'Valor Mínimo',
            'placeholder' => 'Insira o valor mínimo',
            'help'        => 'Valor mínimo de recarga para esta moeda',
        ],
        'rate' => [
            'label'       => 'Taxa',
            'placeholder' => 'Insira taxa de câmbio',
            'help'        => 'Taxa relativa à moeda base',
        ],
        'enabled' => [
            'label' => 'Ativado',
            'help'  => 'Uma moeda habilitada está disponível para uso no sistema',
        ],
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
    ],

    'status' => [
        'active'    => 'Ativo',
        'inactive'  => 'Inativo',
        'default'   => 'Padrão',
    ],

    'buttons' => [
        'add'          => 'Adicionar Moeda',
        'save'         => 'Salvar',
        'cancel'       => 'Cancelar',
        'delete'       => 'Excluir',
        'edit'         => 'Editar',
        'actions'      => 'Ações',
        'update_rates' => 'Atualizar Taxas',
    ],

    'messages' => [
        'currency_not_found'    => 'Moeda não encontrada.',
        'save_success'          => 'Moeda salva com sucesso.',
        'delete_success'        => 'Moeda excluída com sucesso.',
        'update_rates_success'  => 'Taxas de câmbio atualizadas com sucesso.',
        'default_currency_delete'=> 'Não é possível excluir a moeda padrão.',
        'no_permission.manage'  => 'Você não tem permissão para gerenciar moedas.',
        'no_permission.delete'  => 'Você não tem permissão para excluir moedas.',
    ],

    'confirms' => [
        'delete_currency' => 'Tem certeza de que deseja excluir esta moeda? Esta ação não pode ser desfeita.',
        'set_default'     => 'Tem certeza de que deseja definir esta moeda como padrão? Todas as taxas serão recalculadas.',
    ],
];
