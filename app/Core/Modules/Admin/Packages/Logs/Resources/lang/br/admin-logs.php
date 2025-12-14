<?php

return [
    'title' => 'Registro de Log',
    'description' => 'Visualizar e gerenciar registros do sistema',

    'labels' => [
        'select_file' => 'Selecionar arquivo de log',
        'log_file' => 'Arquivo',
        'size' => 'Tamanho',
        'modified' => 'Modificado',
        'level' => 'Nível',
        'date' => 'Data',
        'channel' => 'Canal',
        'message' => 'Mensagem',
        'details' => 'Detalhes',
        'filter_by_level' => 'Todos os níveis',
        'no_logs' => 'Nenhum log encontrado',
        'no_logs_description' => 'Nenhuma entrada de log encontrada para os filtros selecionados',
        'main' => 'Principal',
        'entries' => 'entradas',
        'entries_loaded' => 'entradas carregadas',
        'context_data' => 'Dados de contexto',
        'search_placeholder' => 'Buscar nos logs...',
        'of' => 'de',
    ],

    'level_labels' => [
        'debug' => 'Depuração',
        'info' => 'Informação',
        'notice' => 'Aviso',
        'warning' => 'Atenção',
        'error' => 'Erro',
        'critical' => 'Crítico',
        'alert' => 'Alerta',
        'emergency' => 'Emergência',
    ],

    'refresh' => 'Atualizar',
    'download' => 'Download com detalhes',
    'all_levels' => 'Todos os níveis',
    'show_context' => 'Contexto',
    'show_more' => 'Mostrar mais',
    'show_less' => 'Mostrar menos',

    'clear_log' => 'Limpar log',
    'clear_confirm' => 'Tem certeza de que deseja limpar este arquivo de log?',
    'cleared_success' => 'Arquivo de log limpo com sucesso',
    'cleared_error' => 'Erro ao limpar o arquivo de log',

    'export_error' => 'Erro ao exportar o arquivo de log',
    'export_success' => 'Arquivo de log preparado para download',

    'no_log_selected' => 'Nenhum arquivo de log selecionado',
    'auto_refresh_enabled' => 'Atualização automática ativada',
    'auto_refresh_disabled' => 'Atualização automática desativada',
    'load_more' => 'Carregar mais entradas',
    'search_logs' => 'Buscar nos logs',
    'page' => 'Página',
    'previous' => 'Anterior',
    'next' => 'Próxima',
];
