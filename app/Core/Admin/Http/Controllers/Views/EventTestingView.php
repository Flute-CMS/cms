<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class EventTestingView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.event_testing');
    }

    public function index(FluteRequest $request)
    {
        $events = [
            [
                'name' => 'flute.password_reset_completed',
                'label' => __('admin.event_testing.password_reset_completed'),
                'parameters' => [
                    ['name' => 'user_id', 'label' => __('admin.event_testing.user_id'), 'type' => 'text']
                ]
            ],
            [
                'name' => 'flute.password_reset_requested',
                'label' => __('admin.event_testing.password_reset_requested'),
                'parameters' => [
                    ['name' => 'user_id', 'label' => __('admin.event_testing.user_id'), 'type' => 'text'],
                    ['name' => 'token', 'label' => __('admin.event_testing.token'), 'type' => 'text']
                ]
            ],
            [
                'name' => 'flute.social_logged_in',
                'label' => __('admin.event_testing.social_logged_in'),
                'parameters' => [
                    ['name' => 'user_id', 'label' => __('admin.event_testing.user_id'), 'type' => 'text']
                ]
            ],
            [
                'name' => 'flute.user_logged_in',
                'label' => __('admin.event_testing.user_logged_in'),
                'parameters' => [
                    ['name' => 'user_id', 'label' => __('admin.event_testing.user_id'), 'type' => 'text']
                ]
            ],
            [
                'name' => 'flute.user_registered',
                'label' => __('admin.event_testing.user_registered'),
                'parameters' => [
                    ['name' => 'user_id', 'label' => __('admin.event_testing.user_id'), 'type' => 'text']
                ]
            ],
            [
                'name' => 'flute.user_verified',
                'label' => __('admin.event_testing.user_verified'),
                'parameters' => [
                    ['name' => 'user_id', 'label' => __('admin.event_testing.user_id'), 'type' => 'text']
                ]
            ],
            [
                'name' => 'payment.success',
                'label' => __('admin.event_testing.payment_success'),
                'parameters' => [
                    ['name' => 'invoice_id', 'label' => __('admin.event_testing.invoice_id'), 'type' => 'text']
                ]
            ]
        ];

        if (class_exists(\Flute\Modules\Shop\src\Events\BuyProductEvent::class)) {
            $events[] = [
                'name' => 'flute.shop.buy',
                'label' => __('admin.event_testing.buy_product'),
                'parameters' => [
                    ['name' => 'product_id', 'label' => __('admin.event_testing.product_id'), 'type' => 'text']
                ]
            ];
        }

        return view("Core/Admin/Http/Views/pages/event_testing/index", [
            'events' => $events,
            'request' => $request
        ]);
    }
}