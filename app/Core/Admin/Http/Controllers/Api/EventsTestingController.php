<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Flute\Core\Auth\Events\{
    PasswordResetCompletedEvent,
    PasswordResetRequestedEvent,
    SocialLoggedInEvent,
    UserLoggedInEvent,
    UserRegisteredEvent,
    UserVerifiedEvent
};
use Flute\Core\Payments\Events\{
    PaymentFailedEvent,
    PaymentSuccessEvent
};
use Omnipay\Common\Message\ResponseInterface;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\PasswordResetToken;

class EventsTestingController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.event_testing');
    }

    public function check(FluteRequest $fluteRequest)
    {
        $eventName = $fluteRequest->get('event');
        $parameters = $fluteRequest->get('params');

        $event = $this->createEvent($eventName, $parameters);
        if ($event) {
            if ($event instanceof JsonResponse)
                return $event;

            events()->dispatch($event, $event::NAME);
            return response()->json(['message' => __('admin.event_testing.success')]);
        }

        return response()->json(['error' => __('admin.event_testing.invalid_event')], 400);
    }

    private function createEvent(string $eventName, array $parameters)
    {
        if (isset($parameters['user_id'])) {
            $userId = $parameters['user_id'];
            $user = user()->get($userId);

            if (!$user) {
                return response()->json(['error' => __('admin.event_testing.user_not_found')], 404);
            }
        }

        switch ($eventName) {
            case PasswordResetCompletedEvent::NAME:
                return new PasswordResetCompletedEvent($user);
            case PasswordResetRequestedEvent::NAME:
                $token = new PasswordResetToken;
                $token->token = $parameters['token'];
                $token->user = $user;
                $token->expiry = now();

                return $token ? new PasswordResetRequestedEvent($user, $token) : null;
            case SocialLoggedInEvent::NAME:
                return new SocialLoggedInEvent($user);
            case UserLoggedInEvent::NAME:
                return new UserLoggedInEvent($user);
            case UserRegisteredEvent::NAME:
                return new UserRegisteredEvent($user);
            case UserVerifiedEvent::NAME:
                return new UserVerifiedEvent($user);
            case 'flute.shop.buy':
                if (class_exists(\Flute\Modules\Shop\src\Events\BuyProductEvent::class)) {
                    $product = rep(\Flute\Modules\Shop\database\Entities\ShopProduct::class)->findByPK($parameters['product_id'] ?? 0);
                    return $product ? new \Flute\Modules\Shop\src\Events\BuyProductEvent($product) : null;
                }
                return false;
            case PaymentFailedEvent::NAME:
                $response = app(ResponseInterface::class);
                return new PaymentFailedEvent($response);
            case PaymentSuccessEvent::NAME:
                $invoice = rep(PaymentInvoice::class)->findOne([
                    'transactionId' => $parameters['invoice_id'] ?? 0
                ]);
                return $invoice ? new PaymentSuccessEvent($invoice, user()->get($invoice->user->id)) : null;
            default:
                return null;
        }
    }
}