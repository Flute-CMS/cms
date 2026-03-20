<?php

namespace Flute\Core\Listeners;

use Flute\Core\Events\ResponseEvent;

class HeadersListener
{
    public static function onRouteResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        // $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob:; style-src 'self' 'unsafe-inline'; img-src 'self' data: https: http: blob:; font-src 'self' data:; connect-src 'self' https:; media-src 'self'; object-src 'none'; worker-src 'self' blob:; frame-src 'self'; frame-ancestors 'self';");
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        if (request()->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        $appKey = (string) config('app.key');
        if (empty($appKey)) {
            logs()->warning('Application key (app.key) is not set. Using fallback for auth token HMAC.');
        }

        if (!session()->has('auth_token')) {
            $state = user()->isLoggedIn() ? '1:' . ( user()->id ?? '0' ) : '0:guest';
            $secret = !empty($appKey) ? $appKey : hash('sha256', __DIR__ . session()->getId());
            $seed = bin2hex(random_bytes(8));
            $token = hash_hmac('sha256', $state . '|' . session()->getId() . '|' . $seed, $secret);
            session()->set('auth_token', $token);
        }
        $authToken = session()->get('auth_token');

        if (request()->getMethod() === 'HEAD') {
            $response->headers->set('Cache-Control', 'no-cache');
        }

        $justLoggedInAt = session()->get('just_logged_in_at');
        $justLoggedInRecent = is_int($justLoggedInAt) && $justLoggedInAt > ( time() - 10 );

        if (
            $justLoggedInRecent
            || request()->htmx()->isHtmxRequest()
            || request()->htmx()->isBoosted()
            || is_development()
        ) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            if ($justLoggedInRecent) {
                session()->remove('just_logged_in_at');
            }
        } elseif (is_performance()) {
            if (user()->isLoggedIn()) {
                $response->setCache([
                    'private' => true,
                    'max_age' => 300,
                ]);
            } else {
                $response->setCache([
                    'public' => true,
                    'max_age' => 900,
                    's_maxage' => 1800,
                ]);
            }
        } else {
            if (!$response->headers->has('Cache-Control') || $response->headers->get('Cache-Control') === 'no-cache') {
                if (user()->isLoggedIn()) {
                    $response->setCache([
                        'private' => true,
                        'no_cache' => true,
                        'must_revalidate' => true,
                    ]);
                } else {
                    $response->setCache([
                        'public' => true,
                        'max_age' => 60,
                        's_maxage' => 120,
                    ]);
                }
            }
        }

        if ($response->getStatusCode() === 200 && !$response->headers->has('ETag')) {
            $content = $response->getContent();
            if ($content !== false && strlen($content) < 512000) {
                $response->setEtag(md5($content));
            }
        }

        $varyHeaders = array_unique(array_merge($response->getVary(), [
            'HX-Request',
            'HX-Boosted',
            'Cookie',
            'Authorization',
        ]));
        $response->setVary($varyHeaders, false);
    }
}
