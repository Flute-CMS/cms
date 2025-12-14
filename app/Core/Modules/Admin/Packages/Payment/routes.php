<?php

use Flute\Core\Router\Router;

Router::screen('/admin/payment/gateways', \Flute\Admin\Packages\Payment\Screens\PaymentGatewayScreen::class);
Router::screen('/admin/payment/gateways/add', \Flute\Admin\Packages\Payment\Screens\EditPaymentGatewayScreen::class);
Router::screen('/admin/payment/gateways/{id}/edit', \Flute\Admin\Packages\Payment\Screens\EditPaymentGatewayScreen::class);
Router::screen('/admin/payment/promo-codes', \Flute\Admin\Packages\Payment\Screens\PromoCodeScreen::class);
Router::screen('/admin/payment/invoices', \Flute\Admin\Packages\Payment\Screens\PaymentInvoiceScreen::class);
