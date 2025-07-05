<?php

return [
    "edit" => [
        "title" => "Editar Perfil",

        "main" => [
            "title"       => "Configurações Principais",
            "description" => "Aqui você pode alterar as configurações principais da sua conta.",
            "info_title"  => "Informações Básicas",
            "info_description" => "Alguns dados podem ser visíveis para outros usuários.",

            "fields" => [
                "name"                  => "Nome",
                "email"                 => "E-mail",
                "password"              => "Senha",
                "email_verified"        => "E-mail verificado",
                "email_not_verified"    => "E-mail não verificado",
                "password_not_set"      => "Não definida",
                "password_not_provided" => "Não fornecida",
                "last_changed"          => "Última alteração",
                "verify_email"          => "Verificar e-mail",
            ],

            "password_description" => "Uma senha forte ajuda a proteger sua conta.",

            "basic_information" => [
                "title"       => "Informações Básicas",
                "description" => "Alterar as informações básicas do seu perfil.",

                "fields" => [
                    "name"                 => "Nome",
                    "name_placeholder"     => "Digite seu nome completo",
                    "name_info"            => "Este nome ficará visível para todos os usuários do site",

                    "login"                => "Nome de usuário",
                    "login_placeholder"    => "Digite seu nome de usuário",
                    "login_info"           => "Seu nome de usuário está visível apenas para você e é usado para login",

                    "uri"                  => "URL do Perfil",
                    "uri_placeholder"      => "Insira sua URL",
                    "uri_info"             => "Insira o slug para a URL do seu perfil. Por exemplo: :example",

                    "email"                => "E-mail",
                    "email_placeholder"    => "Digite seu endereço de e-mail",
                ],

                "save_changes"         => "Salvar alterações",
                "save_changes_success" => "Informações básicas atualizadas com sucesso.",
            ],

            "profile_images" => [
                "title"       => "Imagens do Perfil",
                "description" => "Carregue seu avatar e banner para personalizar seu perfil.",

                "fields" => [
                    "avatar" => "Avatar",
                    "banner" => "Banner",
                ],

                "save_changes"         => "Salvar imagem",
                "save_changes_success" => "Imagens de perfil atualizadas com sucesso.",
            ],

            "change_password" => [
                "title"       => "Alterar Senha",
                "description" => "Altere sua senha atual para maior segurança.",

                "fields" => [
                    "current_password"                => "Senha atual",
                    "current_password_placeholder"    => "Insira a senha atual",

                    "new_password"                    => "Nova senha",
                    "new_password_placeholder"        => "Inserir nova senha",

                    "confirm_new_password"            => "Confirme a nova senha",
                    "confirm_new_password_placeholder"=> "Repetir nova senha",
                ],

                "save_changes"         => "Alterar senha",
                "save_changes_success" => "Senha alterada com sucesso.",
                "current_password_incorrect" => "Senha atual incorreta.",
                "passwords_do_not_match"      => "As senhas não coincidem.",
            ],

            "delete_account" => [
                "title"       => "Excluir conta",
                "description" => "Excluir sua conta resultará na perda permanente de todos os seus dados.",
                "confirm_message" => "Tem certeza de que deseja excluir sua conta? Todos os seus dados serão removidos permanentemente.",

                "fields" => [
                    "confirmation"             => "Confirmar exclusão",
                    "confirmation_placeholder" => "Digite seu nome de usuário para confirmar",
                ],

                "delete_button"       => "Excluir Conta",
                "delete_success"      => "Sua conta foi deletada com sucesso.",
                "delete_failed"       => "Confirmação incorreta. Conta não foi deletada.",
                "confirmation_error"  => "Por favor, informe seu nome de usuário corretamente.",
            ],

            "profile_privacy" => [
                "title"       => "Privacidade do Perfil",
                "description" => "Configure as configurações de privacidade do seu perfil.",

                "fields" => [
                    "hidden"  => [
                        "label" => "Público",
                        "info"  => "Seu perfil está visível para todos os usuários.",
                    ],
                    "visible" => [
                        "label" => "Privado",
                        "info"  => "Seu perfil está oculto para outros usuários.",
                    ],
                ],

                "save_changes_success" => "Configurações de privacidade atualizadas com sucesso.",
            ],

            "profile_theme" => [
                "title"       => "Tema do Sistema",
                "description" => "Selecione o tema para o sistema inteiro.",

                "fields" => [
                    "light" => [
                        "label" => "Tema claro",
                        "info"  => "Adequado para hora do dia.",
                    ],
                    "dark"  => [
                        "label" => "Tema escuro",
                        "info"  => "Ideal para trabalhar à noite.",
                    ],
                ],

                "save_changes"         => "Salvar tema",
                "save_changes_success" => "Tema do perfil atualizado com sucesso.",
            ],
        ],

        "settings" => [
            "title" => "Configurações",
        ],

        "social" => [
            "title"               => "Integrações",
            "description"         => "Conecte-se às redes sociais para login rápido e acesso a recursos adicionais.",
            "unlink"              => "Desvincular",
            "unlink_description"  => "Tem certeza de que deseja desvincular esta rede social?",
            "default_link"        => "Link padrão",
            "connect"             => "Link",
            "no_socials"          => "Infelizmente, não existem redes sociais em nosso sistema 😢",
            "show_description"    => "Mostrar rede social para outros usuários",
            "hide_description"    => "Ocultar rede social de outros usuários",
            "last_social_network" => "Para desvincular uma rede social, defina uma senha.",
        ],

        "payments" => [
            "title"       => "Pagamentos",
            "description" => "Histórico de pagamentos e transações.",
            "table"       => [
                "id"          => "ID",
                "date"        => "Data",
                "gateway"     => "Método de pagamento",
                "amount"      => "Valor",
                "status"      => "Status",
                "promo"       => "Código promocional",
                "transaction" => "Transação",
                "actions"     => "Ações",
            ],
            "status" => [
                "paid"    => "Pago",
                "pending" => "Pendente",
            ],
        ],

        "upload_directory_error" => "O diretório de upload não existe.",
        "upload_failed"          => "Falha ao carregar :field.",
    ],

    "protection_warning"        => "Defina uma senha para proteger sua conta. <a href=\":link\">Defina uma</a>",
    "no_profile_modules_info"   => "Nenhum módulo de perfil está instalado no Flute. <a href=\":link\">Ver no marketplace</a>",
    "was_online"                => "Esteve online :data",
    "view"                      => "Ver perfil",
    "social_deleted"            => "Rede social desvinculada com sucesso!",
    "member_since"              => "Membro desde :date",
    "hidden_warning"            => "Seu perfil está oculto para outros usuários.",
    "profile_hidden"            => "Este perfil está oculto para outros usuários.",
    "verification_warning"      => "Verifique seu endereço de e-mail para acessar recursos adicionais. <a href=\":link\">Verificar</a>",
];
