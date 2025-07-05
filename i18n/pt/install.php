<?php

return [
    "title" => "Instalação do Flute CMS",

    "welcome" => [
        "title"       => "Bem-vindo ao Flute CMS",    ],

    "requirements" => [        "php"             => "PHP",
        "extensions"      => "Extensões",
        "directories"     => "Diretórios",
        "continue"        => "Continuar",        "writable_error"  => "Diretório não é gravável",
        "fix_errors"      => "Por favor, corrija todos os erros antes de prosseguir",
    ],

    "common" => [
        "next"           => "Próximo passo",
        "back"           => "Passo anterior",
        "finish"         => "Finalizar instalação",
        "finish_success" => "Instalação concluída com sucesso!",
    ],

    "flute_key" => [
        "title"          => "Chave de Licença",        "placeholder"    => "Digitar chave de licença",        "error_empty"    => "Chave da licença é obrigatória",        "label"          => "Chave de licença (opcional)",    ],

    "database" => [
        "heading"                   => "Configuração do Banco de Dados",        "driver"                    => "Tipo de Banco de Dados",
        "host"                      => "Host",
        "port"                      => "Porta",
        "database"                  => "Nome do Banco de Dados",
        "username"                  => "Usuário",
        "password"                  => "Senha",
        "prefix"                    => "Prefixo da Tabela",        "test_connection"           => "Teste de conexão",
        "connection_success"        => "Conexão do banco de dados estabelecida com sucesso",
        "error_host_required"       => "O host é obrigatório",
        "error_database_required"   => "Nome do banco de dados é obrigatório",
        "error_sqlite_dir"          => "Falha ao criar diretório para SQLite",
        "error_driver_not_supported"=> "O driver do banco de dados selecionado não é suportado",
    ],

    "admin_user" => [
        "heading"              => "Criar Administrador",
        "subheading"           => "Crie uma conta de administrador para gerenciar o Flute CMS",
        "name"                 => "Nome completo",
        "email"                => "E-mail",
        "login"                => "Nome de usuário",        "password"             => "Senha",
        "password_confirmation"=> "Confirmar senha",
        "create_user"          => "Criar Administrador",
        "creation_success"     => "Administrador criado com sucesso! Agora você pode prosseguir para a próxima etapa.",
        "error_name_required"  => "Nome completo é necessário",
        "error_email_required" => "E-mail é obrigatório",
        "error_email_invalid"  => "Por favor insira um endereço de e-mail válido",
        "error_login_required" => "Nome de usuário é obrigatório",
        "error_password_required"=> "Senha é obrigatória",
        "error_password_length"=> "A senha deve ter pelo menos 8 caracteres",
        "error_password_mismatch"=> "As senhas não coincidem",
    ],

    "site_info" => [        "subheading"         => "Configure as configurações básicas do seu site",
        "name"               => "Nome do site",
        "description"        => "Descrição do site",        "url"                => "URL do Site",        "timezone"           => "Fuso horário",        "tab_basics"         => "Configurações",
        "tab_seo"            => "SEO",
        "basic_section"      => "Informações Básicas",        "meta_title"         => "Título SEO",
        "meta_description"   => "Descrição SEO",        "seo_tips_title"     => "Dicas de SEO",    ],

    "site_settings" => [        "tab_general"           => "Configurações",
        "tab_security"          => "Segurança",
        "general_section"       => "Configurações do site",
        "appearance_section"    => "Aparência",
        "security_section"      => "Configurações de segurança",
        "cron_mode"             => "Modo Cron",
        "cron_mode_desc"        => "Habilite o modo cron. Você precisa configurar o crontab para que as tarefas do cron funcionem.",
        "maintenance_mode"      => "Modo de manutenção",        "flute_copyright"       => "Menção Flute",        "csrf_enabled"          => "Proteção CSRF",        "convert_to_webp"       => "Imagens WebP",    ],
];
