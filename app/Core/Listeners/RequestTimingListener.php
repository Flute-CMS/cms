<?php

namespace Flute\Core\Listeners;

use function defined;

use Flute\Core\Database\DatabaseTimingLogger;
use Flute\Core\Events\ResponseEvent;

use const FLUTE_DB_SETUP_END;
use const FLUTE_DEFERRED_SAVE_END;
use const FLUTE_DISPATCH_END;
use const FLUTE_EVENTS_END;
use const FLUTE_ROUTER_START;

class RequestTimingListener
{
    public static function onRouteResponse(ResponseEvent $event): void
    {
        $now = microtime(true);
        $start = defined('FLUTE_ROUTER_START') ? (float) FLUTE_ROUTER_START : ($now - 0.0);
        $total = max(0.0, $now - $start);

        // Phase timings (may be null if constants are not defined)
        $dbSetupEnd = defined('FLUTE_DB_SETUP_END') ? (float) FLUTE_DB_SETUP_END : null;
        $dispatchEnd = defined('FLUTE_DISPATCH_END') ? (float) FLUTE_DISPATCH_END : null;
        $eventsEnd = defined('FLUTE_EVENTS_END') ? (float) FLUTE_EVENTS_END : null;
        $deferredEnd = defined('FLUTE_DEFERRED_SAVE_END') ? (float) FLUTE_DEFERRED_SAVE_END : null;

        $db = DatabaseTimingLogger::getTotalTime();
        $dbCount = DatabaseTimingLogger::getTotalCount();
        $dbPct = $total > 0 ? round(($db / $total) * 100, 1) : 0.0;

        $mem = memory_get_peak_usage(true);
        $memMb = round($mem / (1024 * 1024), 1);

        $req = request();
        $path = $req->getRequestUri();
        $method = $req->getMethod();
        $ip = $req->getClientIp();
        $userId = user()->isLoggedIn() ? (int) user()->id : null;

        $thresholdMs = is_development() ? 0 : 300; // log all in dev, otherwise if > 300ms
        $totalMs = (int) round($total * 1000);

        $context = [
            'method' => $method,
            'path' => $path,
            'ip' => $ip,
            'user_id' => $userId,
            'total_ms' => $totalMs,
            'db_ms' => (int) round($db * 1000),
            'db_count' => $dbCount,
            'db_pct' => $dbPct,
            'mem_mb' => $memMb,
            'phase_db_setup_ms' => $dbSetupEnd ? (int) round(($dbSetupEnd - $start) * 1000) : null,
            'phase_dispatch_ms' => ($dbSetupEnd && $dispatchEnd) ? (int) round(($dispatchEnd - $dbSetupEnd) * 1000) : null,
            'phase_events_ms' => ($dispatchEnd && $eventsEnd) ? (int) round(($eventsEnd - $dispatchEnd) * 1000) : null,
            'phase_deferred_ms' => ($eventsEnd && $deferredEnd) ? (int) round(($deferredEnd - $eventsEnd) * 1000) : null,
        ];

        if ($totalMs >= $thresholdMs || $dbPct >= 40.0) {
            logs()->info('request.summary', $context);
        } else {
            is_debug() && logs()->debug('request.summary', $context);
        }
    }
}
