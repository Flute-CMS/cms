<?php

return [
    'vars' => [
        'name' => 'Nome do Usuário',
        'ip' => 'Endereço IP',
        'device' => 'Dispositivo / Navegador',
        'time' => 'Data e Hora',
        'amount' => 'Valor',
        'balance' => 'Saldo Atual',
        'gateway' => 'Método de Pagamento',
        'transaction_id' => 'ID da Transação',
    ],

    'welcome' => [
        'title' => 'Bem-vindo, {name}!',
        'content' => 'Obrigado por se registrar. Ficamos felizes em ver você!',
    ],

    'new_device_login' => [
        'title' => 'Login em novo dispositivo',
        'content' => 'Um login a partir de um novo dispositivo foi detectado: {device} (IP: {ip}) em {time}. Se não foi você, altere sua senha imediatamente.',
    ],

    'password_changed' => [
        'title' => 'Senha alterada',
        'content' => 'Sua senha foi alterada em {time}. Se você não fez essa alteração, entre em contato com o suporte imediatamente.',
    ],

    'payment_success' => [
        'title' => 'Pagamento realizado com sucesso',
        'content' => 'Seu pagamento de {amount} via {gateway} foi processado. Transação: {transaction_id}.',
        'view_history' => 'Histórico de pagamentos',
    ],

    'balance_topup' => [
        'title' => 'Saldo recarregado',
        'content' => 'Seu saldo foi recarregado em {amount}. Saldo atual: {balance}.',
    ],

    'invoice_created' => [
        'title' => 'Fatura criada',
        'content' => 'Uma fatura de {amount} foi criada via {gateway}. Complete o pagamento para recarregar seu saldo.',
        'pay_now' => 'Pagar agora',
    ],

    'email_verified' => [
        'title' => 'E-mail confirmado',
        'content' => 'Seu endereço de e-mail foi verificado com sucesso. Todos os recursos estão agora disponíveis.',
    ],
];