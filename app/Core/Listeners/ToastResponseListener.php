<?php

namespace Flute\Core\Listeners;

use Flute\Core\Events\ResponseEvent;
use Flute\Core\Services\ToastService;

class ToastResponseListener
{
    public static function onRouteResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $request = request();

        /** @var ToastService $toastService */
        $toastService = app(ToastService::class);
        $toasts = $toastService->getToasts();

        if (empty($toasts)) {
            return;
        }

        if ($request->isXmlHttpRequest() || $request->headers->has('HX-Request')) {
            $response->headers->set('X-Toasts', json_encode($toasts));
        }
    }
}
