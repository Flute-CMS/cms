<?php

namespace Flute\Core\ServerQuery\Drivers;

use Flute\Core\ServerQuery\QueryDriverInterface;
use Flute\Core\ServerQuery\QueryResult;

/**
 * FiveM / RedM (cfx.re) HTTP Query Protocol.
 *
 * Uses the server's built-in HTTP endpoints to fetch information.
 * Default port: 30120.
 *
 * @link https://docs.fivem.net/docs/server-manual/proxy-setup/
 */
class FiveMQueryDriver implements QueryDriverInterface
{
    public function query(string $ip, int $port, int $timeout = 3, array $settings = []): QueryResult
    {
        $result = new QueryResult();
        $result->game = 'fivem';

        $baseUrl = "http://{$ip}:{$port}";

        // Fetch dynamic info (hostname, player count, map)
        $dynamicJson = $this->httpGet("{$baseUrl}/dynamic.json", $timeout);

        if ($dynamicJson === null) {
            return $result;
        }

        $dynamic = json_decode($dynamicJson, true);

        if (!is_array($dynamic)) {
            return $result;
        }

        $result->online = true;
        $result->hostname = $dynamic['hostname'] ?? null;
        $result->players = $dynamic['clients'] ?? 0;
        $result->maxPlayers = $dynamic['sv_maxclients'] ?? 0;
        $result->map = $dynamic['mapname'] ?? 'San Andreas';

        // Fetch static server info (resources, vars, version)
        $infoJson = $this->httpGet("{$baseUrl}/info.json", $timeout);

        if ($infoJson !== null) {
            $info = json_decode($infoJson, true);

            if (is_array($info)) {
                $result->version = $info['version'] ?? null;
                $result->additional = [
                    'resources' => $info['resources'] ?? [],
                    'vars' => $info['vars'] ?? [],
                    'gamename' => $info['vars']['gamename'] ?? 'gta5',
                ];

                $gameName = $info['vars']['gamename'] ?? 'gta5';
                if ($gameName === 'rdr3') {
                    $result->game = 'redm';
                }
            }
        }

        // Fetch player list
        $playersJson = $this->httpGet("{$baseUrl}/players.json", $timeout);

        if ($playersJson !== null) {
            $players = json_decode($playersJson, true);

            if (is_array($players)) {
                foreach ($players as $player) {
                    $result->playersData[] = [
                        'name' => $player['name'] ?? 'Unknown',
                        'score' => 0,
                        'time' => (float) ( $player['ping'] ?? 0 ),
                    ];
                }

                if (!empty($result->playersData)) {
                    $result->players = count($result->playersData);
                }
            }
        }

        return $result;
    }

    /**
     * HTTP GET with curl (preferred) or file_get_contents fallback.
     * curl works on more hosting environments and handles timeouts better.
     */
    private function httpGet(string $url, int $timeout): ?string
    {
        if (function_exists('curl_init')) {
            return $this->curlGet($url, $timeout);
        }

        return $this->streamGet($url, $timeout);
    }

    private function curlGet(string $url, int $timeout): ?string
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            return null;
        }

        return $response;
    }

    private function streamGet(string $url, int $timeout): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        return $response === false ? null : $response;
    }
}
