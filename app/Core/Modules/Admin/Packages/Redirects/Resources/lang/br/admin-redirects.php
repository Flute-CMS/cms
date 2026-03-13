<?php

return [
    'title' => 'Redirecionamentos',
    'description' => 'Gerenciar redirecionamentos de URL com condições',

    'fields' => [
        'from_url' => [
            'label' => 'De URL',
            'placeholder' => '/pagina-antiga',
            'help' => 'O caminho da URL para redirecionar (ex.: /pagina-antiga)',
        ],
        'to_url' => [
            'label' => 'Para URL',
            'placeholder' => '/pagina-nova',
            'help' => 'A URL de destino para redirecionar',
        ],
        'conditions' => [
            'label' => 'Condições',
            'help' => 'Condições opcionais que devem ser atendidas para o redirecionamento ser ativado',
        ],
        'condition_type' => [
            'label' => 'Tipo',
            'placeholder' => 'Selecione o tipo de condição',
        ],
        'condition_operator' => [
            'label' => 'Operador',
            'placeholder' => 'Selecione o operador',
        ],
        'condition_value' => [
            'label' => 'Valor',
            'placeholder' => 'Digite o valor',
        ],
    ],

    'condition_types' => [
        'ip' => 'Endereço IP',
        'cookie' => 'Cookie',
        'referer' => 'Referência',
        'request_method' => 'Método HTTP',
        'user_agent' => 'User Agent',
        'header' => 'Cabeçalho HTTP',
        'lang' => 'Idioma',
    ],

    'operators' => [
        'equals' => 'Igual a',
        'not_equals' => 'Diferente de',
        'contains' => 'Contém',
        'not_contains' => 'Não contém',
    ],

    'buttons' => [
        'add' => 'Adicionar Redirecionamento',
        'save' => 'Salvar',
        'edit' => 'Editar',
        'delete' => 'Excluir',
        'actions' => 'Ações',
        'add_condition_group' => 'Adicionar grupo de condições',
        'add_condition' => 'Adicionar condição',
        'remove_condition' => 'Remover',
        'clear_cache' => 'Limpar Cache',
    ],

    'messages' => [
        'save_success' => 'Redirecionamento salvo com sucesso.',
        'update_success' => 'Redirecionamento atualizado com sucesso.',
        'delete_success' => 'Redirecionamento excluído com sucesso.',
        'not_found' => 'Redirecionamento não encontrado.',
        'cache_cleared' => 'Cache de redirecionamentos limpo com sucesso.',
        'route_conflict' => 'Atenção: a URL ":url" conflita com uma rota existente ":route". O redirecionamento pode não funcionar porque a rota tem prioridade.',
        'from_url_required' => 'O campo "De URL" é obrigatório.',
        'to_url_required' => 'O campo "Para URL" é obrigatório.',
        'same_urls' => 'A URL "De" e "Para" não podem ser iguais.',
    ],

    'empty' => [
        'title' => 'Nenhum redirecionamento ainda',
        'sub' => 'Crie seu primeiro redirecionamento para gerenciar o encaminhamento de URLs',
    ],

    'confirms' => [
        'delete' => 'Tem certeza de que deseja excluir este redirecionamento? Esta ação não pode ser desfeita.',
    ],

    'table' => [
        'from' => 'De',
        'to' => 'Para',
        'conditions' => 'Condições',
        'actions' => 'Ações',
    ],

    'modal' => [
        'create_title' => 'Criar Redirecionamento',
        'edit_title' => 'Editar Redirecionamento',
        'conditions_title' => 'Condições do Redirecionamento',
        'conditions_help' => 'Entre grupos de condições usa-se lógica OU, dentro do grupo — E.',
        'group_label' => 'Grupo :number',
    ],

    'settings' => [
        'title' => 'Configurações',
        'cache_time' => [
            'label' => 'Duração do Cache (segundos)',
            'help' => 'Quanto tempo as regras de redirecionamento são armazenadas em cache. Defina 0 para desativar.',
            'placeholder' => '3600',
        ],
    ],

    'alert' => [
        'route_conflict_title' => 'Conflito de Rota Detectado',
    ],
];
