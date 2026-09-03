<?php

namespace Flute\Core\Listeners;

use Flute\Core\Database\DatabaseTimingLogger;
use Flute\Core\Events\ResponseEvent;
use Flute\Core\Modules\Page\Services\WidgetRenderTiming;
use Flute\Core\Router\RoutingTiming;
use Flute\Core\Services\SessionService;
use Flute\Core\Template\TemplateRenderTiming;

class HeadersListener
{
    public static function onRouteResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        /** @var SessionService $session */
        $session = session();
        $sessionName = $session->getName();
        $hasSessionCookie = $sessionName !== '' && isset($_COOKIE[$sessionName]);
        $shouldUseSession = !$session->isClosed() && ( $session->isStarted() || $hasSessionCookie );
        $isLoggedIn = $shouldUseSession && $session->has('user_id');

        header_remove('X-Powered-By');

        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none';");
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()',
        );
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('Cross-Origin-Resource-Policy', 'cross-origin');
        $response->headers->set('X-DNS-Prefetch-Control', 'on');

        if (request()->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'text/html') || !$response->headers->has('Content-Type')) {
            $linkHeaders = [
                '</assets/fonts/manrope/Manrope-Regular.woff2>; rel=preload; as=font; type="font/woff2"; crossorigin',
                '</assets/fonts/manrope/Manrope-Medium.woff2>; rel=preload; as=font; type="font/woff2"; crossorigin',
                '</assets/js/htmx/core.js>; rel=preload; as=script',
            ];

            $response->headers->set('Link', implode(', ', $linkHeaders), false);
        }

        $appKey = (string) config('app.key');
        if (empty($appKey)) {
            logs()->warning('Application key (app.key) is not set. Using fallback for auth token HMAC.');
        }

        if ($shouldUseSession && !$session->has('auth_token')) {
            $userId = $isLoggedIn ? (string) $session->get('user_id', '0') : '0';
            $state = $isLoggedIn ? '1:' . $userId : '0:guest';
            $secret = !empty($appKey) ? $appKey : hash('sha256', __DIR__ . $session->getId());
            $seed = bin2hex(random_bytes(8));
            $token = hash_hmac('sha256', $state . '|' . $session->getId() . '|' . $seed, $secret);
            $session->set('auth_token', $token);
        }

        if (request()->getMethod() === 'HEAD') {
            $response->headers->set('Cache-Control', 'no-cache');
        }

        $justLoggedInAt = $shouldUseSession ? $session->get('just_logged_in_at') : null;
        $justLoggedInRecent = is_int($justLoggedInAt) && $justLoggedInAt > ( time() - 10 );

        if (
            $justLoggedInRecent
            || request()->htmx()->isHtmxRequest()
            || request()->htmx()->isBoosted()
            || is_development()
        ) {
            $response->setCache([
                'no_cache' => true,
                'no_store' => true,
                'must_revalidate' => true,
                'private' => true,
            ]);
            $response->setExpires(new \DateTimeImmutable('@0'));
            if ($justLoggedInRecent) {
                $session->remove('just_logged_in_at');
            }
        } elseif (is_performance()) {
            $contentType = (string) $response->headers->get('Content-Type', '');
            $isHtmlResponse = str_contains($contentType, 'text/html') || !$response->headers->has('Content-Type');

            if ($isHtmlResponse) {
                // HTML pages must not be browser-cached with public max-age because
                // CDNs/proxies often ignore Vary on non-standard headers (HX-Request,
                // HX-Boosted).  A cached full-page response served to an htmx partial
                // request causes the layout shell (header, footer) to be duplicated.
                // Server-side page cache (App::tryServePageCache) already provides the
                // fast-path for anonymous full-page GETs, so the browser only needs a
                // short must-revalidate window.
                if ($isLoggedIn) {
                    $response->setCache([
                        'private' => true,
                        'no_cache' => true,
                        'must_revalidate' => true,
                    ]);
                } else {
                    $response->setCache([
                        'private' => true,
                        'no_cache' => true,
                        'must_revalidate' => true,
                    ]);
                    $response->headers->addCacheControlDirective('s-maxage', '0');
                }
            } else {
                if ($isLoggedIn) {
                    $response->setCache([
                        'private' => true,
                        'max_age' => 300,
                    ]);
                    $response->headers->addCacheControlDirective('stale-while-revalidate', '600');
                } else {
                    $response->setCache([
                        'public' => true,
                        'max_age' => 900,
                        's_maxage' => 1800,
                    ]);
                    $response->headers->addCacheControlDirective('stale-while-revalidate', '86400');
                }
            }
        } else {
            if (!$response->headers->has('Cache-Control') || $response->headers->get('Cache-Control') === 'no-cache') {
                if ($isLoggedIn) {
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
        $varyHeaders = ['HX-Request', 'HX-Boosted', 'HX-Preloaded', 'X-Flute-Prefetch'];

        if ($isLoggedIn) {
            $varyHeaders[] = 'Cookie';
            $varyHeaders[] = 'Authorization';
        }

        $varyHeaders = array_unique(array_merge($response->getVary(), $varyHeaders));
        $response->setVary($varyHeaders, false);

        self::attachServerTimingHeader($response);
    }

    private static function attachServerTimingHeader($response): void
    {
        if (!is_debug()) {
            return;
        }

        $request = request();
        if (method_exists($request, 'isPrefetch') && $request->isPrefetch()) {
            return;
        }

        $now = microtime(true);
        $start = defined('FLUTE_ROUTER_START') ? (float) constant('FLUTE_ROUTER_START') : $now;
        $appMs = max(0.0, ( $now - $start ) * 1000);

        $dbMs = DatabaseTimingLogger::getTotalTime() * 1000;
        $dbCount = DatabaseTimingLogger::getTotalCount();
        $viewTimes = TemplateRenderTiming::all();
        $viewMs = array_sum($viewTimes) * 1000;
        $viewCount = count($viewTimes);
        $widgetMs = WidgetRenderTiming::getTotalTime() * 1000;
        $widgetCount = WidgetRenderTiming::getTotalCount();
        $routeMs = array_sum(RoutingTiming::all()) * 1000;

        $metrics = [
            self::serverTimingMetric('app', $appMs),
            self::serverTimingMetric('db', $dbMs, 'queries:' . $dbCount),
            self::serverTimingMetric('views', $viewMs, 'count:' . $viewCount),
            self::serverTimingMetric('widgets', $widgetMs, 'count:' . $widgetCount),
            self::serverTimingMetric('routing', $routeMs),
        ];

        $response->headers->set('Server-Timing', implode(', ', $metrics));
        $response->headers->set('X-Flute-DB-Queries', (string) $dbCount);
        $response->headers->set('X-Flute-Views', (string) $viewCount);
        $response->headers->set('X-Flute-Widgets', (string) $widgetCount);
        $response->headers->set('X-Flute-Query-Summary', self::querySummaryHeader());
        $response->headers->set('X-Flute-Widget-Summary', self::widgetSummaryHeader());
    }

    private static function serverTimingMetric(string $name, float $durationMs, ?string $description = null): string
    {
        $metric = $name . ';dur=' . number_format(max(0.0, $durationMs), 2, '.', '');

        if ($description !== null) {
            $metric .= ';desc="' . str_replace(['"', '\\'], '', $description) . '"';
        }

        return $metric;
    }

    private static function querySummaryHeader(): string
    {
        $queries = DatabaseTimingLogger::getQueries();
        if (empty($queries)) {
            return '';
        }

        $groups = [];
        foreach ($queries as $query) {
            $statement = preg_replace('/\s+/', ' ', (string) ( $query['query'] ?? '' ));
            $statement = substr($statement, 0, 90);

            if (!isset($groups[$statement])) {
                $groups[$statement] = [
                    'count' => 0,
                    'time' => 0.0,
                ];
            }

            $groups[$statement]['count']++;
            $groups[$statement]['time'] += (float) ( $query['time'] ?? 0.0 );
        }

        uasort($groups, static fn($a, $b) => $b['count'] <=> $a['count']);

        $summary = [];
        foreach (array_slice($groups, 0, 5, true) as $statement => $group) {
            $summary[] = sprintf(
                '%dx %.1fms %s',
                $group['count'],
                $group['time'] * 1000,
                str_replace(['"', '\\', "\r", "\n"], '', $statement),
            );
        }

        return implode(' | ', $summary);
    }

    private static function widgetSummaryHeader(): string
    {
        $widgets = WidgetRenderTiming::all();
        if (empty($widgets)) {
            return '';
        }

        arsort($widgets);

        $counts = WidgetRenderTiming::counts();
        $summary = [];
        foreach (array_slice($widgets, 0, 5, true) as $widget => $seconds) {
            $summary[] = sprintf(
                '%s %dx %.1fms',
                str_replace(['"', '\\', "\r", "\n"], '', (string) $widget),
                $counts[$widget] ?? 1,
                $seconds * 1000,
            );
        }

        return implode(' | ', $summary);
    }
}
