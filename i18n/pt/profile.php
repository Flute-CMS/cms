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
                    "email_placeholder"    => "Enter your email address",
                ],

                "save_changes"         => "Salvar alterações",
                "save_changes_success" => "Basic information updated successfully.",
            ],

            "profile_images" => [
                "title"       => "Profile Images",
                "description" => "Upload your avatar and banner to personalize your profile.",

                "fields" => [
                    "avatar" => "Avatar",
                    "banner" => "Banner",
                ],

                "save_changes"         => "Salvar imagem",
                "save_changes_success" => "Profile images updated successfully.",
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
                "save_changes_success" => "Password changed successfully.",
                "current_password_incorrect" => "Current password is incorrect.",
                "passwords_do_not_match"      => "Passwords do not match.",
            ],

            "delete_account" => [
                "title"       => "Excluir conta",
                "description" => "Deleting your account will result in permanent loss of all your data.",
                "confirm_message" => "Are you sure you want to delete your account? All your data will be permanently removed.",

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
                        "info"  => "Ideal for working at night.",
                    ],
                ],

                "save_changes"         => "Salvar tema",
                "save_changes_success" => "Profile theme updated successfully.",
            ],
        ],

        "settings" => [
            "title" => "Configurações",
        ],

        "social" => [
            "title"               => "Integrações",
            "description"         => "Connect social networks for quick login and access to additional features.",
            "unlink"              => "Desvincular",
            "unlink_description"  => "Are you sure you want to unlink this social network?",
            "default_link"        => "Link padrão",
            "connect"             => "Link",
            "no_socials"          => "Unfortunately, there are no social networks in our system 😢",
            "show_description"    => "Show social network to other users",
            "hide_description"    => "Hide social network from other users",
            "last_social_network" => "To unlink a social network, set a password.",
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

        "upload_directory_error" => "Upload directory does not exist.",
        "upload_failed"          => "Failed to upload :field.",
    ],

    "protection_warning"        => "Set a password to protect your account. <a href=\":link\">Set it</a>",
    "no_profile_modules_info"   => "No profile modules are installed in Flute. <a href=\":link\">View on marketplace</a>",
    "was_online"                => "Esteve online :data",
    "view"                      => "Ver perfil",
    "social_deleted"            => "Rede social desvinculada com sucesso!",
    "member_since"              => "Membro desde :date",
    "hidden_warning"            => "Your profile is hidden from other users.",
    "profile_hidden"            => "This profile is hidden from other users.",
    "verification_warning"      => "Verify your email address to access additional features. <a href=\":link\">Verify</a>",
];
