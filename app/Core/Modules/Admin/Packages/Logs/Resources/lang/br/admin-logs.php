<?php

return [
    'title' => 'Registo de Log',
    'description' => 'Visualizar e gerenciar logs do sistema',

    'labels' => [
        'select_file' => 'Selecione arquivo de log',
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
        'main' => 'Principal',
    ],

    'level_labels' => [
        'debug' => 'Debug',
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
    'show_more' => 'Mostre mais',
    'show_less' => 'Mostrar menos',

    'clear_log' => 'Limpar Log',
    'clear_confirm' => 'Tem certeza de que deseja limpar este arquivo de log?',
    'cleared_success' => 'Arquivo de log limpo com sucesso',
    'cleared_error' => 'Erro ao limpar arquivo de log',

    'export_error' => 'Erro ao exportar arquivo de log',
    'export_success' => 'Arquivo de log preparado para download',
];
