<?php

return [
    "edit" => [
        "title" => "Editar perfil",

        "main" => [
            "title"       => "Configurações principais",
            "description" => "Aqui você pode alterar as principais configurações da sua conta.",
            "info_title"  => "Informações básicas",
            "info_description" => "Alguns dados podem estar visíveis para outros usuários.",

            "fields" => [
                "name"                  => "Nome",
                "email"                 => "E-mail",
                "password"              => "Senha",
                "email_verified"        => "E-mail verificado",
                "email_not_verified"    => "E-mail não verificado",
                "password_not_set"      => "Não definido",
                "password_not_provided" => "Não informado",
                "last_changed"          => "Última alteração",
                "verify_email"          => "Verificar e-mail",
            ],

            "password_description" => "Uma senha forte ajuda a proteger sua conta.",

            "basic_information" => [
                "title"       => "Informações básicas",
                "description" => "Altere as informações básicas do seu perfil.",

                "fields" => [
                    "name"                 => "Nome",
                    "name_placeholder"     => "Digite seu nome completo",
                    "name_info"            => "Este nome ficará visível para todos os usuários do site",

                    "login"                => "Nome de usuário",
                    "login_placeholder"    => "Digite seu nome de usuário",
                    "login_info"           => "Seu nome de usuário é visível apenas para você e é usado para fazer login",

                    "uri"                  => "URL do perfil",
                    "uri_placeholder"      => "Digite sua URL",
                    "uri_info"             => "Digite o slug da URL do seu perfil. Por exemplo: :example",

                    "email"                => "E-mail",
                    "email_placeholder"    => "Digite seu endereço de e-mail",
                ],

                "save_changes" => "Salvar alterações",
                "save_changes_success" => "Informações básicas atualizadas com sucesso.",
                "email_confirmation_sent" => "Um e-mail de confirmação foi enviado para o novo endereço.",
                "pending_email_notice" => "Confirmação pendente: :email",
                "pending_email_cancelled" => "Alteração de e-mail cancelada.",
                "resend_confirmation" => "Reenviar",
                "cancel_change" => "Cancelar",
                "email_changed_success" => "E-mail alterado com sucesso.",
                "email_password_required" => "Confirme sua senha atual para alterar o e-mail.",
            ],

            "profile_images" => [
                "title"       => "Imagens do perfil",
                "description" => "Envie seu avatar e banner para personalizar seu perfil.",

                "fields" => [
                    "avatar" => "Avatar",
                    "banner" => "Banner",
                ],

                "save_changes"         => "Salvar imagens",
                "save_changes_success" => "Imagens do perfil atualizadas com sucesso.",
            ],

            "change_password" => [
                "title"       => "Alterar senha",
                "description" => "Altere sua senha atual para aumentar a segurança.",

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
                "login_and_email_required"    => "Login e e-mail são necessários para definir uma senha.",
            ],

            "delete_account" => [
                "title"       => "Excluir conta",
                "description" => "Excluir sua conta resultará na perda permanente de todos os seus dados.",
                "confirm_message" => "Tem certeza de que deseja excluir sua conta? Todos os seus dados serão removidos permanentemente.",

                "fields" => [
                    "confirmation"             => "Confirmação de exclusão",
                    "confirmation_placeholder" => "Digite seu nome de usuário para confirmar",
                ],

                "delete_button"       => "Excluir conta",
                "delete_success"      => "Sua conta foi excluída com sucesso.",
                "delete_failed"       => "Confirmação incorreta. A conta não foi excluída.",
                "confirmation_error"  => "Digite seu nome de usuário corretamente.",
            ],

            "profile_privacy" => [
                "title"       => "Privacidade do perfil",
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
                "title"       => "Tema do sistema",
                "description" => "Selecione o tema para todo o sistema.",

                "fields" => [
                    "light" => [
                        "label" => "Tema claro",
                        "info"  => "Adequado para o período diurno.",
                    ],
                    "dark"  => [
                        "label" => "Tema escuro",
                        "info"  => "Ideal para trabalhar à noite.",
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

        "notifications" => [
            "title"       => "Notificações",
            "description" => "Gerencie como e onde você recebe notificações.",

            "sound_title"          => "Som de notificação",
            "sound_description"    => "Reproduzir um som quando novas notificações chegarem.",
            "sound_label"          => "Som de notificação",
            "sound_hint"           => "Quando ativado, um breve som será reproduzido sempre que você receber uma nova notificação.",

            "channels_title"       => "Canais de notificação",
            "channels_description" => "Ativar ou desativar globalmente os canais de notificação.",

            "channels" => [
                "inapp"      => "Notificações no aplicativo",
                "inapp_desc" => "Receba notificações dentro da plataforma.",
                "email"      => "Notificações por e-mail",
                "email_desc" => "Receba notificações no seu endereço de e-mail.",
            ],

            "templates_title"       => "Tipos de notificação",
            "templates_description" => "Ajuste quais notificações você deseja receber em cada canal.",

            "core_module"   => "Sistema",
            "save_success"  => "Configurações de notificação salvas com sucesso.",
        ],

        "settings" => [
            "title" => "Configurações",
        ],

        "social" => [
            "title"               => "Integrações",
            "description"         => "Conecte redes sociais para fazer login rapidamente e acessar recursos adicionais.",
            "unlink"              => "Desvincular",
            "unlink_description"  => "Tem certeza de que deseja desvincular esta rede social?",
            "default_link"        => "Vínculo padrão",
            "connect"             => "Link",
            "no_socials"          => "Infelizmente, não há redes sociais em nosso sistema 😢",
            "show_description"    => "Mostrar rede social para outros usuários",
            "hide_description"    => "Ocultar rede social de outros usuários",
            "last_social_network" => "Para desvincular uma rede social, defina uma senha.",
            "linked"              => "Vinculada",
            "not_linked"          => "Não vinculada",
            "linked_at"           => "Vinculada em :date",
            "visible"             => "Visível para outros",
            "hidden"              => "Oculto",
        ],

        "payments" => [
            "title"       => "Pagamentos",
            "description" => "Histórico de pagamentos e transações.",
            "balance"     => "Saldo",
            "top_up"      => "Adicionar saldo",
            "invoices_title" => "Histórico de pagamentos",
            "invoices_description" => "Todas as suas transações e recargas de saldo.",
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
        "balance_history" => [
            "title" => "Histórico do saldo",
            "description" => "Todas as operações de saldo: recargas, compras e reembolsos.",
            "table" => [
                "type"        => "Tipo",
                "description" => "Descrição",
                "amount"      => "Valor",
                "date"        => "Data",
            ],
            "types" => [
                "topup"    => "Recarga",
                "purchase" => "Compra",
                "refund"   => "Reembolso",
                "admin"    => "Administrador",
            ],
            "no_description" => "Sem descrição",
        ],
    ],

    "two_factor" => [
        "title" => "Autenticação de dois fatores",
        "description" => "Proteja sua conta com uma camada adicional de segurança.",
        "status_enabled" => "Ativado",
        "status_disabled" => "Desativado",
        "last_enabled" => "Ativado em :date",
    ],

    "protection_warning"        => "Defina uma senha para proteger sua conta. <a href=\":link\">Definir</a>",
    "no_profile_modules_info"   => "Nenhum módulo de perfil está instalado no Flute. <a href=\":link\">Ver no marketplace</a>",
    "was_online"                => "Esteve online :date",
    "view"                      => "Ver perfil",
    "social_deleted"            => "Rede social desvinculada com sucesso!",
    "social_binded"             => "Rede social vinculada com sucesso!",
    "member_since"              => "Membro desde :date",
    "hidden_warning"            => "Seu perfil está oculto para outros usuários.",
    "profile_hidden"            => "Este perfil está oculto para outros usuários.",
    "verification_warning"      => "Verifique seu endereço de e-mail para acessar recursos adicionais. <a href=\":link\">Verificar</a>",
    "verify_email"              => "Verificar e-mail",

    "admin_actions" => [
        "add_balance" => "Adicionar saldo",
        "remove_balance" => "Remover saldo",
        "ban_user" => "Banir usuário",
        "unban_user" => "Desbanir usuário",
        "verify_user" => "Verificar e-mail",
        "unverify_user" => "Remover verificação de e-mail",
        "clear_sessions" => "Limpar sessões",
        "current_balance" => "Saldo atual",
        "amount" => "Valor",
        "amount_placeholder" => "Digite o valor",
        "max_amount" => "Máximo: :amount",
        "ban_reason" => "Motivo do banimento",
        "ban_reason_placeholder" => "Digite o motivo do banimento",
        "ban_until" => "Banir até",
        "ban_until_hint" => "Deixe vazio para banimento permanente",
        "balance_added" => "Saldo adicionado: :amount",
        "balance_added_by" => "Adicionado pelo administrador :admin",
        "balance_removed" => "Saldo removido: :amount",
        "balance_removed_by" => "Removido pelo administrador :admin",
        "user_banned" => "Usuário banido",
        "user_unbanned" => "Usuário desbanido",
        "user_verified" => "E-mail verificado",
        "user_unverified" => "Verificação de e-mail removida",
        "user_approved" => "Usuário aprovado",
        "user_unapproved" => "Aprovação do usuário removida",
        "approve_user" => "Aprovar usuário",
        "unapprove_user" => "Remover aprovação",
        "approve_confirm" => "Tem certeza de que deseja aprovar este usuário?",
        "unapprove_confirm" => "Tem certeza de que deseja remover a aprovação deste usuário?",
        "sessions_cleared" => "Sessões do usuário limpas",
        "cant_ban_self" => "Você não pode banir a si mesmo",
        "cant_clear_own_sessions" => "Você não pode limpar suas próprias sessões",
        "unban_confirm" => "Tem certeza de que deseja desbanir este usuário?",
        "clear_sessions_confirm" => "Tem certeza de que deseja limpar todas as sessões deste usuário?",
    ],
    "errors" => [
        "social_binded" => "Esta rede social já está vinculada a outra conta.",
        "social_delay" => "Aguarde antes de vincular uma rede social novamente.",
    ],
    "banner_alt" => "Banner do perfil de :name",
    "avatar_alt" => "Avatar de :name",
    "social_networks" => "Redes sociais",
    "visit_social" => "Visitar :network",
    "profile_tabs" => "Abas do perfil",
];
