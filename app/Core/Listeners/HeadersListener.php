<?php

namespace Flute\Core\Listeners;

use Flute\Core\Events\ResponseEvent;

class HeadersListener
{
    public static function onRouteResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none';");
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        if (request()->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Link headers for 103 Early Hints (picked up by CDNs like Cloudflare)
        $contentType = (string) $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'text/html') || !$response->headers->has('Content-Type')) {
            $linkHeaders = [
                '</assets/fonts/manrope/Manrope-Regular.woff2>; rel=preload; as=font; type="font/woff2"; crossorigin',
                '</assets/fonts/manrope/Manrope-Medium.woff2>; rel=preload; as=font; type="font/woff2"; crossorigin',
            ];
            $response->headers->set('Link', implode(', ', $linkHeaders), false);
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

        // Boosted GET navigations (clicking hx-boost links) — allow short private cache
        // so that hover-prefetch → click works instantly from browser cache.
        // Non-boosted HTMX requests (Yoyo components, polling) stay no-store.
        $isBoostedGet = request()->htmx()->isBoosted()
            && request()->getMethod() === 'GET'
            && !$justLoggedInRecent
            && !is_development();

        if ($isBoostedGet) {
            $response->setCache([
                'private' => true,
                'max_age' => 5,
            ]);
        } elseif (
            $justLoggedInRecent
            || request()->htmx()->isHtmxRequest()
            || request()->htmx()->isBoosted()
            || is_development()
        ) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
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
                $response->headers->set('Cache-Control', $response->headers->get('Cache-Control') . ', stale-while-revalidate=600');
            } else {
                $response->setCache([
                    'public' => true,
                    'max_age' => 900,
                    's_maxage' => 1800,
                ]);
                $response->headers->set('Cache-Control', $response->headers->get('Cache-Control') . ', stale-while-revalidate=86400');
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

        // ETag is useful only for conditional requests (304 Not Modified).
        // Skip when Cache-Control already prevents caching or max_age is very short.
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $hasNoCacheDirective = str_contains($cacheControl, 'no-store') || str_contains($cacheControl, 'no-cache');

        if ($response->getStatusCode() === 200 && !$response->headers->has('ETag') && !$hasNoCacheDirective) {
            $content = $response->getContent();
            if ($content !== false && strlen($content) < 512000) {
                $response->setEtag(md5($content));
            }
        }

        // Vary: Cookie effectively disables CDN caching for anonymous users
        // (each session cookie = unique cache entry). Only add it for authenticated users.
        $varyHeaders = ['HX-Request', 'HX-Boosted'];

        if (user()->isLoggedIn()) {
            $varyHeaders[] = 'Cookie';
            $varyHeaders[] = 'Authorization';
        }

        $varyHeaders = array_unique(array_merge($response->getVary(), $varyHeaders));
        $response->setVary($varyHeaders, false);
    }
}
