<?php

namespace Flute\Core\ServerQuery\Drivers;

use Flute\Core\ServerQuery\QueryDriverInterface;
use Flute\Core\ServerQuery\QueryResult;

/**
 * SA-MP / open.mp Query Protocol.
 *
 * @link https://www.open.mp/docs/tutorials/QueryMechanism
 */
class SampQueryDriver implements QueryDriverInterface
{
    private const MAGIC = 'SAMP';

    private const OPCODE_INFO = 'i';

    private const OPCODE_PLAYERS_DETAILED = 'd';

    public function query(string $ip, int $port, int $timeout = 3, array $settings = []): QueryResult
    {
        $result = new QueryResult();
        $result->game = 'samp';

        $address = "udp://{$ip}:{$port}";
        $socket = @stream_socket_client($address, $errno, $errstr, $timeout);

        if (!$socket) {
            logs()->warning("SampQuery: connect failed for {$address} (errno={$errno}: {$errstr})");

            return $result;
        }

        stream_set_blocking($socket, true);
        stream_set_timeout($socket, $timeout);

        try {
            $header = $this->buildHeader($ip, $port);

            // Query server info (opcode 'i')
            fwrite($socket, $header . self::OPCODE_INFO);
            $response = @fread($socket, 4096);

            if ($response === false || strlen($response) < 11) {
                logs()->debug("SampQuery: no response from {$address}");

                return $result;
            }

            // Verify SAMP signature
            if (substr($response, 0, 4) !== self::MAGIC) {
                return $result;
            }

            $offset = 11; // Skip 11-byte header echo

            if (( $offset + 1 ) > strlen($response)) {
                return $result;
            }

            $hasPassword = ord($response[$offset++]);

            if (( $offset + 4 ) > strlen($response)) {
                return $result;
            }

            $result->players = unpack('v', substr($response, $offset, 2))[1];
            $offset += 2;
            $result->maxPlayers = unpack('v', substr($response, $offset, 2))[1];
            $offset += 2;

            // Hostname
            if (( $offset + 4 ) <= strlen($response)) {
                $hostnameLen = unpack('V', substr($response, $offset, 4))[1];
                $offset += 4;

                if (( $offset + $hostnameLen ) <= strlen($response)) {
                    $result->hostname = substr($response, $offset, $hostnameLen);
                    $offset += $hostnameLen;
                }
            }

            // Gamemode
            if (( $offset + 4 ) <= strlen($response)) {
                $modeLen = unpack('V', substr($response, $offset, 4))[1];
                $offset += 4;

                if (( $offset + $modeLen ) <= strlen($response)) {
                    $result->map = substr($response, $offset, $modeLen);
                    $offset += $modeLen;
                }
            }

            // Language
            if (( $offset + 4 ) <= strlen($response)) {
                $langLen = unpack('V', substr($response, $offset, 4))[1];
                $offset += 4;

                if (( $offset + $langLen ) <= strlen($response)) {
                    $result->additional['language'] = substr($response, $offset, $langLen);
                }
            }

            $result->online = true;
            $result->additional['password'] = $hasPassword;

            // Query detailed player list (opcode 'd')
            $result->playersData = $this->queryPlayers($socket, $header);
        } finally {
            fclose($socket);
        }

        return $result;
    }

    /**
     * @return array<int, array{name: string, score: int, time: float}>
     */
    private function queryPlayers($socket, string $header): array
    {
        fwrite($socket, $header . self::OPCODE_PLAYERS_DETAILED);
        $response = @fread($socket, 65535);

        if ($response === '' || strlen($response) < 13) {
            return [];
        }

        $offset = 11; // Skip header echo

        if (( $offset + 2 ) > strlen($response)) {
            return [];
        }

        $playerCount = unpack('v', substr($response, $offset, 2))[1];
        $offset += 2;

        $players = [];

        for ($i = 0; $i < $playerCount; $i++) {
            if (( $offset + 1 ) > strlen($response)) {
                break;
            }

            $playerId = ord($response[$offset++]);

            if (( $offset + 1 ) > strlen($response)) {
                break;
            }

            $nameLen = ord($response[$offset++]);

            if (( $offset + $nameLen ) > strlen($response)) {
                break;
            }

            $name = substr($response, $offset, $nameLen);
            $offset += $nameLen;

            if (( $offset + 8 ) > strlen($response)) {
                break;
            }

            $score = unpack('l', substr($response, $offset, 4))[1];
            $offset += 4;

            $ping = unpack('V', substr($response, $offset, 4))[1];
            $offset += 4;

            $players[] = [
                'name' => $name,
                'score' => $score,
                'time' => (float) $ping,
            ];
        }

        return $players;
    }

    private function buildHeader(string $ip, int $port): string
    {
        $header = self::MAGIC;

        foreach (explode('.', $ip) as $octet) {
            $header .= chr((int) $octet);
        }

        $header .= chr($port & 0xFF);
        $header .= chr(( $port >> 8 ) & 0xFF);

        return $header;
    }
}
