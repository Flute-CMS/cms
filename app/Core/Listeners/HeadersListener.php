<?php

namespace Flute\Core\Listeners;

use Flute\Core\Events\ResponseEvent;

class HeadersListener
{
    public static function onRouteResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        // $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: * blob:; object-src 'none'; worker-src 'self' blob:;");
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Powered-By', 'Flute CMS');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=86400; includeSubDomains');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        $response->headers->set('Is-Logged-In', user()->isLoggedIn() ? 'true' : 'false');
        $response->headers->set('Auth-Token', md5(user()->isLoggedIn() . '_' . (user()->isLoggedIn() ? user()->id : '')));

        if (request()->getMethod() === 'HEAD') {
            $response->headers->set('Cache-Control', 'no-cache');
        }

        $justLoggedInAt = session()->get('just_logged_in_at');
        $justLoggedInRecent = is_int($justLoggedInAt) && ($justLoggedInAt > (time() - 10));

        if ($justLoggedInRecent || request()->htmx()->isHtmxRequest() || request()->htmx()->isBoosted()) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            if ($justLoggedInRecent) {
                session()->remove('just_logged_in_at');
            }
        } elseif (is_performance()) {
            $response->setCache([
                'public' => true,
                'max_age' => 900,
                's_maxage' => 1800,
            ]);
        }

        $varyHeaders = array_unique(array_merge($response->getVary(), ['HX-Request', 'HX-Boosted', 'Cookie', 'Authorization']));
        $response->setVary($varyHeaders, false);
    }
}
