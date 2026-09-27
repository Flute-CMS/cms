<?php

return [
    'vars' => [
        'name' => 'Nome do usuário',
        'ip' => 'Endereço IP',
        'device' => 'Dispositivo / navegador',
        'time' => 'Data e hora',
        'amount' => 'Valor',
        'balance' => 'Saldo atual',
        'gateway' => 'Método de pagamento',
        'transaction_id' => 'ID da transação',
    ],

    'welcome' => [
        'title' => 'Bem-vindo, {name}!',
        'content' => 'Obrigado por se registrar. Ficamos felizes em ter você conosco!',
    ],

    'new_device_login' => [
        'title' => 'Login em um novo dispositivo',
        'content' => 'Foi detectado um login em um novo dispositivo: {device} (IP: {ip}) em {time}. Se não foi você, altere sua senha imediatamente.',
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
        'content' => 'Uma fatura de {amount} foi criada via {gateway}. Conclua o pagamento para recarregar seu saldo.',
        'pay_now' => 'Pagar agora',
    ],

    'email_verified' => [
        'title' => 'E-mail confirmado',
        'content' => 'Seu endereço de e-mail foi verificado com sucesso. Todos os recursos já estão disponíveis.',
    ],
];
