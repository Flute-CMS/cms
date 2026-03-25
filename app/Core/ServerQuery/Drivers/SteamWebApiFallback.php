<?php

namespace Flute\Core\ServerQuery\Drivers;

use Flute\Core\ServerQuery\QueryResult;

/**
 * HTTP fallback for Valve servers when UDP is blocked.
 * Uses Steam Web API (IGameServersService/GetServerList) — no UDP needed.
 *
 * Requires a Steam Web API key.
 * Get one at: https://steamcommunity.com/dev/apikey
 */
class SteamWebApiFallback
{
    private const API_URL = 'https://api.steampowered.com/IGameServersService/GetServerList/v1/';

    /**
     * Query a single server via Steam Web API.
     */
    public static function query(string $ip, int $port, ?string $apiKey): ?QueryResult
    {
        if (empty($apiKey)) {
            return null;
        }

        $url = self::buildUrl($apiKey, $ip, $port);
        $json = self::httpGet($url, 5);

        return self::parseApiResponse($json);
    }

    /**
     * Query multiple servers in parallel via curl_multi.
     *
     * @param array<string, array{ip: string, port: int}> $servers keyed by ID
     * @return array<string, QueryResult> keyed by ID
     */
    public static function queryBatch(array $servers, ?string $apiKey): array
    {
        if (empty($apiKey) || empty($servers)) {
            return [];
        }

        // Use curl_multi for parallel HTTP requests
        if (function_exists('curl_multi_init')) {
            return self::queryBatchCurlMulti($servers, $apiKey);
        }

        // Fallback: sequential
        $results = [];

        foreach ($servers as $id => $cfg) {
            $r = self::query($cfg['ip'], $cfg['port'], $apiKey);

            if ($r !== null) {
                $results[$id] = $r;
            }
        }

        return $results;
    }

    private static function queryBatchCurlMulti(array $servers, string $apiKey): array
    {
        $mh = curl_multi_init();
        $handles = [];

        foreach ($servers as $id => $cfg) {
            $url = self::buildUrl($apiKey, $cfg['ip'], $cfg['port']);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$id] = $ch;
        }

        // Execute all requests in parallel
        do {
            $status = curl_multi_exec($mh, $active);

            if ($active) {
                curl_multi_select($mh, 1);
            }
        } while ($active && $status === CURLM_OK);

        // Collect results
        $results = [];

        foreach ($handles as $id => $ch) {
            $json = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($json !== false && $code < 400) {
                $r = self::parseApiResponse($json);

                if ($r !== null) {
                    $results[$id] = $r;
                }
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);

        return $results;
    }

    private static function buildUrl(string $apiKey, string $ip, int $port): string
    {
        return self::API_URL
        . '?'
        . http_build_query([
            'key' => $apiKey,
            'filter' => "\\addr\\{$ip}:{$port}",
            'limit' => 1,
        ]);
    }

    private static function parseApiResponse(?string $json): ?QueryResult
    {
        if ($json === null) {
            return null;
        }

        $data = json_decode($json, true);
        $servers = $data['response']['servers'] ?? [];

        if (empty($servers)) {
            return null;
        }

        $srv = $servers[0];
        $result = new QueryResult();
        $result->online = true;
        $result->hostname = $srv['name'] ?? null;
        $result->map = $srv['map'] ?? null;
        $result->players = $srv['players'] ?? 0;
        $result->maxPlayers = $srv['max_players'] ?? 0;
        $result->game = isset($srv['appid']) ? (string) $srv['appid'] : null;
        $result->version = $srv['version'] ?? null;
        $result->additional = [
            'description' => $srv['gamedir'] ?? '',
            'app_id' => $srv['appid'] ?? 0,
            'vac' => $srv['secure'] ?? false ? 1 : 0,
            'steam_web_api' => true,
        ];

        return $result;
    }

    private static function httpGet(string $url, int $timeout): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $response !== false && $code < 400 ? $response : null;
        }

        $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'ignore_errors' => true]]);
        $response = @file_get_contents($url, false, $ctx);

        return $response === false ? null : $response;
    }
}
