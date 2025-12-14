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
                "password_not_provided" => "Não informada",
                "last_changed"          => "Última alteração",
                "verify_email"          => "Verificar E-mail",
            ],

            "password_description" => "Uma senha forte ajuda a proteger sua conta.",

            "basic_information" => [
                "title"       => "Informações Básicas",
                "description" => "Altere as informações básicas do seu perfil.",

                "fields" => [
                    "name"                 => "Nome",
                    "name_placeholder"     => "Digite seu nome completo",
                    "name_info"            => "Este nome será visível para todos os usuários do site",

                    "login"                => "Nome de usuário",
                    "login_placeholder"    => "Digite seu nome de usuário",
                    "login_info"           => "Seu nome de usuário é visível apenas para você e é usado para login",

                    "uri"                  => "URL do Perfil",
                    "uri_placeholder"      => "Digite sua URL",
                    "uri_info"             => "Digite o slug para a URL do seu perfil. Exemplo: :example",

                    "email"                => "E-mail",
                    "email_placeholder"    => "Digite seu endereço de E-mail",
                ],

                "save_changes"         => "Salvar alterações",
                "save_changes_success" => "Informações básicas atualizadas com sucesso.",
            ],

            "profile_images" => [
                "title"       => "Imagens do Perfil",
                "description" => "Envie seu avatar e banner para personalizar seu perfil.",

                "fields" => [
                    "avatar" => "Avatar",
                    "banner" => "Banner",
                ],

                "save_changes"         => "Salvar imagens",
                "save_changes_success" => "Imagens do perfil atualizadas com sucesso.",
            ],

            "change_password" => [
                "title"       => "Alterar Senha",
                "description" => "Altere sua senha atual para maior segurança.",

                "fields" => [
                    "current_password"                => "Senha atual",
                    "current_password_placeholder"    => "Digite a senha atual",

                    "new_password"                    => "Nova senha",
                    "new_password_placeholder"        => "Digite a nova senha",

                    "confirm_new_password"            => "Confirmar nova senha",
                    "confirm_new_password_placeholder"=> "Repita a nova senha",
                ],

                "save_changes"         => "Alterar senha",
                "save_changes_success" => "Senha alterada com sucesso.",
                "current_password_incorrect" => "A senha atual está incorreta.",
                "passwords_do_not_match"      => "As senhas não coincidem.",
            ],

            "delete_account" => [
                "title"       => "Excluir Conta",
                "description" => "Excluir sua conta resultará na perda permanente de todos os seus dados.",
                "confirm_message" => "Tem certeza de que deseja excluir sua conta? Todos os seus dados serão removidos permanentemente.",

                "fields" => [
                    "confirmation"             => "Confirmação de exclusão",
                    "confirmation_placeholder" => "Digite seu nome de usuário para confirmar",
                ],

                "delete_button"       => "Excluir Conta",
                "delete_success"      => "Sua conta foi excluída com sucesso.",
                "delete_failed"       => "Confirmação incorreta. A conta não foi excluída.",
                "confirmation_error"  => "Por favor, insira corretamente seu nome de usuário.",
            ],

            "profile_privacy" => [
                "title"       => "Privacidade do Perfil",
                "description" => "Configure as opções de privacidade do seu perfil.",

                "fields" => [
                    "hidden"  => [
                        "label" => "Público",
                        "info"  => "Seu perfil é visível para todos os usuários.",
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
                "description" => "Selecione o tema para todo o sistema.",

                "fields" => [
                    "light" => [
                        "label" => "Tema claro",
                        "info"  => "Adequado para uso durante o dia.",
                    ],
                    "dark"  => [
                        "label" => "Tema escuro",
                        "info"  => "Ideal para uso noturno.",
                    ],
                    "system" => [
                        "label" => "Tema do sistema",
                        "info"  => "O tema será selecionado automaticamente com base no seu dispositivo.",
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
            "description"         => "Conecte redes sociais para login rápido e acesso a recursos adicionais.",
            "unlink"              => "Desvincular",
            "unlink_description"  => "Tem certeza de que deseja desvincular esta rede social?",
            "default_link"        => "Vinculação padrão",
            "connect"             => "Vincular",
            "no_socials"          => "Infelizmente, não há redes sociais disponíveis em nosso sistema 😢",
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
        "upload_failed"          => "Falha ao enviar :field.",
    ],

    "protection_warning"        => "Defina uma senha para proteger sua conta. <a href=\":link\">Definir</a>",
    "no_profile_modules_info"   => "Nenhum módulo de perfil está instalado no Flute. <a href=\":link\">Ver no marketplace</a>",
    "was_online"                => "Estava online em :date",
    "view"                      => "Ver perfil",
    "social_deleted"            => "Rede social desvinculada com sucesso!",
    "social_binded"             => "Rede social vinculada com sucesso!",
    "member_since"              => "Membro desde :date",
    "hidden_warning"            => "Seu perfil está oculto para outros usuários.",
    "profile_hidden"            => "Este perfil está oculto para outros usuários.",
    "verification_warning"      => "Verifique seu endereço de email para acessar recursos adicionais. <a href=\":link\">Verificar</a>",
];
