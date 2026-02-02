<?php

return [
    'title' => 'Backups',
    'description' => 'Gerir backups de módulos, temas e CMS',

    'table' => [
        'type' => 'Tipo',
        'name' => 'Nome',
        'filename' => 'Ficheiro',
        'size' => 'Tamanho',
        'date' => 'Criado',
        'actions' => 'Ações',
        'empty' => 'Ainda não há backups',
    ],

    'types' => [
        'module' => 'Módulo',
        'theme' => 'Tema',
        'modules' => 'Todos os Módulos',
        'themes' => 'Todos os Temas',
        'cms' => 'CMS',
        'full' => 'Backup Completo',
        'vendor' => 'Vendor',
        'composer' => 'Composer',
    ],

    'metrics' => [
        'total_backups' => 'Total de Backups',
        'total_size' => 'Tamanho Total',
    ],

    'actions' => [
        'backup_module' => 'Fazer Backup do Módulo',
        'backup_theme' => 'Fazer Backup do Tema',
        'backup_all_modules' => 'Fazer Backup de Todos os Módulos',
        'backup_all_themes' => 'Fazer Backup de Todos os Temas',
        'backup_cms' => 'Fazer Backup do Núcleo do CMS',
        'backup_full' => 'Backup Completo',
        'download' => 'Descarregar',
        'delete' => 'Eliminar',
        'restore' => 'Restaurar',
        'refresh' => 'Atualizar',
        'create_backup' => 'Criar Backup',
    ],

    'modal' => [
        'backup_module_title' => 'Criar Backup do Módulo',
        'backup_theme_title' => 'Criar Backup do Tema',
        'select_module' => 'Selecionar Módulo',
        'select_theme' => 'Selecionar Tema',
    ],

    'confirmations' => [
        'backup_all_modules' => 'Tem a certeza de que deseja fazer backup de todos os módulos?',
        'backup_all_themes' => 'Tem a certeza de que deseja fazer backup de todos os temas?',
        'backup_cms' => 'Tem a certeza de que deseja fazer backup do núcleo do CMS?',
        'backup_full' => 'Tem a certeza de que deseja criar um backup completo? Isto pode demorar algum tempo.',
        'delete' => 'Tem a certeza de que deseja eliminar este backup?',
        'restore' => 'Tem a certeza de que deseja restaurar a partir deste backup? Os ficheiros atuais serão substituídos.',
    ],

    'messages' => [
        'backup_created' => 'Backup criado: :filename',
        'backup_error' => 'Erro de backup: :message',
        'backup_deleted' => 'Backup eliminado',
        'delete_error' => 'Erro ao eliminar: :message',
        'download_error' => 'Erro ao descarregar: :message',
        'list_refreshed' => 'Lista atualizada',
        'restore_success' => 'Backup restaurado com sucesso. Cache limpa.',
        'restore_error' => 'Erro ao restaurar: :message',
    ],

    'errors' => [
        'module_not_found' => 'Módulo não encontrado',
        'module_path_not_found' => 'Diretório do módulo não encontrado',
        'theme_path_not_found' => 'Diretório do tema não encontrado',
        'modules_path_not_found' => 'Diretório de módulos não encontrado',
        'themes_path_not_found' => 'Diretório de temas não encontrado',
        'cannot_create_zip' => 'Não é possível criar o arquivo ZIP',
        'cannot_open_zip' => 'Não é possível abrir o arquivo ZIP',
        'backup_not_found' => 'Backup não encontrado',
        'cannot_determine_destination' => 'Não é possível determinar o destino de restauração',
        'unknown_backup_type' => 'Tipo de backup desconhecido',
    ],
];
