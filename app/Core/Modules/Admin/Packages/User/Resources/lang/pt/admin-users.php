<?php

return [
    'title' => [        'edit' => 'Editar Usuário: :name',        'reset_password' => 'Redefinir Senha',        'users_and_roles' => 'Usuários e Cargos',
    ],
    'table' => [        'actions' => 'Ações',        'ip' => 'Endereço IP',
        'social_network' => 'Rede Social',
        'value' => 'Valor',
        'display_name' => 'Nome de Exibição',        'payment_gateway' => 'Gateway de Pagamento',
        'amount' => 'Valor',
        'payment_date' => 'Data de Pagamento',        'payment_status' => 'Status do Pagamento',
    ],
    'tabs' => [        'blocked' => 'Bloqueado',
    ],
    'fields' => [
        'avatar' => [
            'label' => 'Avatar',        ],
        'banner' => [
            'label' => 'Banner',        ],
        'name' => [
            'label' => 'Nome',
        ],
        'login' => [
            'label' => 'Nome de usuário',
            'help' => 'Login único do usuário',
        ],
        'email' => [
            'label' => 'E-mail',
        ],
        'uri' => [
            'label' => 'URI',        ],
        'balance' => [
            'label' => 'Saldo',        ],
        'roles' => [
            'label' => 'Cargos',
            'placeholder' => 'Selecionar Cargos',
        ],
        'verified' => [
            'label' => 'Verificado',        ],
        'hidden' => [
            'label' => 'Perfil Oculto',        ],
        'block_reason' => [
            'label' => 'Motivo do Bloqueio',        ],
        'block_until' => [        ],
        'password' => [
            'label' => 'Nova Senha',
            'placeholder' => 'Inserir nova senha',
            'confirm_label' => 'Confirmar Senha',
            'confirm_placeholder' => 'Repetir a nova senha',
        ],
        'social_network' => [
            'label' => 'Rede Social',
            'placeholder' => 'Selecionar rede social',
        ],
        'social_value' => [
            'label' => 'Valor',
            'placeholder' => 'Digite o valor (ex.: usuário ou ID)',
        ],
        'social_url' => [
            'label' => 'URL do Perfil',        ],
        'social_name' => [
            'label' => 'Nome de Exibição',        ],
    ],
    'buttons' => [
        'to_profile' => 'Meu Perfil',
        'cancel' => 'Cancelar',
        'save' => 'Salvar',
        'block' => 'Bloquear',
        'unblock' => 'Desbloquear',
        'reset_password' => 'Redefinir Senha',
        'clear_sessions' => 'Limpar Sessões',
        'delete_user' => 'Deletar Usuário',        'add_social' => 'Adicionar Rede Social',
        'edit_social' => 'Editar',
        'show_social' => 'Exibir',
        'hide_social' => 'Ocultar',
        'delete_social' => 'Excluir',
        'edit' => 'Editar',
        'delete' => 'Excluir',
        'save_social' => 'Salvar',
        'hide' => 'Ocultar',
        'show' => 'Exibir',
    ],
    'sections' => [
        'main_info' => 'Informação Principal',
        'actions' => 'Ações',
        'actions_desc' => 'Ações no usuário.',
    ],
    'confirms' => [    ],
    'modals' => [
        'block_user' => [
            'title' => 'Bloquear Usuário',
        ],
        'reset_password' => [
            'title' => 'Redefinir Senha',
        ],
        'add_social' => [
            'title' => 'Adicionar Rede Social',
        ],
        'edit_social' => [
            'title' => 'Editar Rede Social',
        ],
    ],
    'messages' => [        'no_permission_roles' => 'Você não tem permissão para gerenciar cargos.',        'sessions_cleared' => 'Todas as sessões de usuário terminaram com sucesso.',        'password_reset' => 'Senha de usuário redefinida com sucesso.',    ],
    'status' => [
        'forever' => 'Permanente',
        'unlimited' => 'Ilimitado',
        'blocked_until' => 'Bloqueado até: :date',
        'block_reason' => 'Motivo: :reason',
        'online' => 'Online',
        'offline' => 'Offline',
        'paid' => 'Pago',
        'unpaid' => 'Não pago',
        'verified' => 'Verificado',
        'hidden' => 'Oculto',
        'blocked' => 'Bloqueado',
        'visible' => 'Visível',
    ],
];
