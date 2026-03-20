<?php

namespace Flute\Core\ServerQuery\Drivers;

use Flute\Core\ServerQuery\QueryDriverInterface;
use Flute\Core\ServerQuery\QueryResult;
use RuntimeException;

/**
 * Valve A2S Query Protocol driver.
 *
 * Handles all Source and GoldSrc engine games:
 * CS2, CS:GO, CSS, TF2, Garry's Mod, Rust, ARK, DayZ, 7D2D, Unturned,
 * CS 1.6, Half-Life, and any other game using the Valve Server Query protocol.
 *
 * @link https://developer.valvesoftware.com/wiki/Server_queries
 */
class ValveQueryDriver implements QueryDriverInterface
{
    private const A2S_INFO_REQUEST = "\xFF\xFF\xFF\xFF\x54Source Engine Query\x00";

    private const A2S_PLAYER_REQUEST = "\xFF\xFF\xFF\xFF\x55";

    private const CHALLENGE_PLACEHOLDER = "\xFF\xFF\xFF\xFF";

    private const HEADER_SINGLE = "\xFF\xFF\xFF\xFF";

    private const HEADER_SPLIT = "\xFF\xFF\xFF\xFE";

    private const RESPONSE_INFO_SOURCE = 0x49;

    private const RESPONSE_INFO_GOLDSRC = 0x6D;

    private const RESPONSE_PLAYER = 0x44;

    private const RESPONSE_CHALLENGE = 0x41;

    private int $readTimeout;

    public function query(string $ip, int $port, int $timeout = 3, array $settings = []): QueryResult
    {
        $result = new QueryResult();
        $this->readTimeout = $timeout;

        $queryPort = !empty($settings['query_port']) ? (int) $settings['query_port'] : $port;
        $address = "udp://{$ip}:{$queryPort}";

        // --- A2S_INFO on socket #1 ---
        $socket = @stream_socket_client($address, $errno, $errstr, $timeout);

        if (!$socket) {
            return $result;
        }

        stream_set_timeout($socket, $timeout);

        try {
            $info = $this->queryInfo($socket);
        } finally {
            fclose($socket);
        }

        if ($info === null) {
            return $result;
        }

        $result->online = true;
        $result->hostname = $info['hostname'] ?? null;
        $result->map = $info['map'] ?? null;
        $result->players = $info['players'] ?? 0;
        $result->maxPlayers = $info['max_players'] ?? 0;
        $result->game = $info['game_id'] ?? $info['folder'] ?? null;
        $result->version = $info['version'] ?? null;
        $result->additional = $info;

        // --- A2S_PLAYER on a separate socket ---
        // GoldSrc servers corrupt challenge state when INFO and PLAYER share a socket.
        $socket2 = @stream_socket_client($address, $errno, $errstr, $timeout);

        if (!$socket2) {
            return $result;
        }

        stream_set_timeout($socket2, $timeout);

        try {
            $players = $this->queryPlayers($socket2);

            if ($players !== null && !empty($players)) {
                $filtered = array_filter(
                    $players,
                    static fn($p) => !empty(trim($p['name'])) && isset($p['time']) && $p['time'] < 20000,
                );
                $filtered = array_values($filtered);

                $result->playersData = $filtered;

                if (!empty($filtered)) {
                    $result->players = count($filtered);
                }
            }
        } finally {
            fclose($socket2);
        }

        return $result;
    }

    private function queryInfo($socket): ?array
    {
        $raw = $this->sendAndRead($socket, self::A2S_INFO_REQUEST);

        if ($raw === '') {
            return null;
        }

        [$type, $payload] = $this->parsePacket($raw);

        // CS2+ servers may require a challenge token for A2S_INFO
        if ($type === self::RESPONSE_CHALLENGE && strlen($payload) >= 4) {
            $challenge = substr($payload, 0, 4);
            $raw = $this->sendAndRead($socket, self::A2S_INFO_REQUEST . $challenge);

            if ($raw === '') {
                return null;
            }

            [$type, $payload] = $this->parsePacket($raw);
        }

        if ($type === self::RESPONSE_INFO_SOURCE) {
            return $this->parseSourceInfo($payload);
        }

        if ($type === self::RESPONSE_INFO_GOLDSRC) {
            return $this->parseGoldSrcInfo($payload);
        }

        return null;
    }

    private function queryPlayers($socket): ?array
    {
        $raw = $this->sendAndRead($socket, self::A2S_PLAYER_REQUEST . self::CHALLENGE_PLACEHOLDER);

        if ($raw === '') {
            return null;
        }

        [$type, $payload] = $this->parsePacket($raw);

        // Some servers send multiple challenges before responding.
        // Retry up to 3 times, bail if server keeps sending challenges (anti-flood protection).
        $attempts = 0;

        while ($type === self::RESPONSE_CHALLENGE && strlen($payload) >= 4 && $attempts < 3) {
            $challenge = substr($payload, 0, 4);
            $raw = $this->sendAndRead($socket, self::A2S_PLAYER_REQUEST . $challenge);

            if ($raw === '') {
                return null;
            }

            [$type, $payload] = $this->parsePacket($raw);
            $attempts++;
        }

        if ($type !== self::RESPONSE_PLAYER || $payload === '') {
            return null;
        }

        return $this->parsePlayerList($payload);
    }

    /**
     * Parse Source engine A2S_INFO response (header 0x49).
     */
    private function parseSourceInfo(string $data): ?array
    {
        if ($data === '') {
            return null;
        }

        $offset = 0;
        $info = [];

        $info['protocol'] = $this->readUint8($data, $offset);
        $info['hostname'] = $this->readCString($data, $offset);
        $info['map'] = $this->readCString($data, $offset);
        $info['folder'] = $this->readCString($data, $offset);
        $info['description'] = $this->readCString($data, $offset);

        if (( $offset + 2 ) > strlen($data)) {
            return $info;
        }

        $info['app_id'] = $this->readUint16($data, $offset);
        $info['players'] = $this->readUint8($data, $offset);
        $info['max_players'] = $this->readUint8($data, $offset);
        $info['bots'] = $this->readUint8($data, $offset);

        if (( $offset + 2 ) <= strlen($data)) {
            $info['server_type'] = chr($this->readUint8($data, $offset));
            $info['platform'] = chr($this->readUint8($data, $offset));
        }

        if ($offset < strlen($data)) {
            $info['password'] = $this->readUint8($data, $offset);
        }

        if ($offset < strlen($data)) {
            $info['vac'] = $this->readUint8($data, $offset);
        }

        if ($offset < strlen($data)) {
            $info['version'] = $this->readCString($data, $offset);
        }

        // Extra Data Flag
        if ($offset < strlen($data)) {
            $edf = $this->readUint8($data, $offset);

            if ($edf & 0x80 && ( $offset + 2 ) <= strlen($data)) {
                $info['game_port'] = $this->readUint16($data, $offset);
            }

            if ($edf & 0x10 && ( $offset + 8 ) <= strlen($data)) {
                $info['steam_id'] = $this->readUint64($data, $offset);
            }

            if ($edf & 0x40 && ( $offset + 2 ) <= strlen($data)) {
                $info['stv_port'] = $this->readUint16($data, $offset);
                $info['stv_name'] = $this->readCString($data, $offset);
            }

            if ($edf & 0x20 && $offset < strlen($data)) {
                $info['keywords'] = $this->readCString($data, $offset);
            }

            if ($edf & 0x01 && ( $offset + 8 ) <= strlen($data)) {
                $info['game_id'] = $this->readUint64($data, $offset);
            }
        }

        return $info;
    }

    /**
     * Parse GoldSrc A2S_INFO response (header 0x6D).
     */
    private function parseGoldSrcInfo(string $data): ?array
    {
        if ($data === '') {
            return null;
        }

        $offset = 0;
        $info = [];

        $info['address'] = $this->readCString($data, $offset);
        $info['hostname'] = $this->readCString($data, $offset);
        $info['map'] = $this->readCString($data, $offset);
        $info['folder'] = $this->readCString($data, $offset);
        $info['description'] = $this->readCString($data, $offset);

        if (( $offset + 7 ) > strlen($data)) {
            return $info;
        }

        $info['players'] = $this->readUint8($data, $offset);
        $info['max_players'] = $this->readUint8($data, $offset);
        $info['protocol'] = $this->readUint8($data, $offset);
        $info['server_type'] = chr($this->readUint8($data, $offset));
        $info['platform'] = chr($this->readUint8($data, $offset));
        $info['password'] = $this->readUint8($data, $offset);
        $isMod = $this->readUint8($data, $offset);

        if ($isMod === 1 && $offset < strlen($data)) {
            $info['mod_url'] = $this->readCString($data, $offset);
            $info['mod_download'] = $this->readCString($data, $offset);

            if ($offset < strlen($data)) {
                $offset++; // null byte
            }

            if (( $offset + 4 ) <= strlen($data)) {
                $info['mod_version'] = $this->readUint32($data, $offset);
            }

            if (( $offset + 4 ) <= strlen($data)) {
                $info['mod_size'] = $this->readUint32($data, $offset);
            }

            if ($offset < strlen($data)) {
                $info['mod_multiplayer_only'] = $this->readUint8($data, $offset);
            }

            if ($offset < strlen($data)) {
                $info['mod_custom_dll'] = $this->readUint8($data, $offset);
            }
        }

        if ($offset < strlen($data)) {
            $info['vac'] = $this->readUint8($data, $offset);
        }

        if ($offset < strlen($data)) {
            $info['bots'] = $this->readUint8($data, $offset);
        }

        return $info;
    }

    /**
     * Parse A2S_PLAYER response.
     *
     * @return array<int, array{name: string, score: int, time: float}>
     */
    private function parsePlayerList(string $data): array
    {
        if ($data === '') {
            return [];
        }

        $offset = 0;
        $playerCount = $this->readUint8($data, $offset);
        $players = [];

        for ($i = 0; $i < $playerCount; $i++) {
            if ($offset >= strlen($data)) {
                break;
            }

            $offset++; // index byte (always 0)
            $name = $this->readCString($data, $offset);

            if (( $offset + 8 ) > strlen($data)) {
                break;
            }

            $score = unpack('l', substr($data, $offset, 4))[1];
            $offset += 4;

            $time = unpack('f', substr($data, $offset, 4))[1];
            $offset += 4;

            $players[] = [
                'name' => $name,
                'score' => $score,
                'time' => (float) $time,
            ];
        }

        return $players;
    }

    private function sendAndRead($socket, string $payload, int $maxSize = 4096): string
    {
        fwrite($socket, $payload);

        return $this->readResponse($socket, $maxSize);
    }

    /**
     * Read a full response, handling split packets.
     * Uses stream_select for reliable timeouts on shared hosting.
     */
    private function readResponse($socket, int $maxSize = 4096): string
    {
        $data = $this->readWithTimeout($socket, $maxSize);

        if ($data === '') {
            return '';
        }

        if (strlen($data) < 4) {
            return $data;
        }

        // Single packet
        if (substr($data, 0, 4) === self::HEADER_SINGLE) {
            return $data;
        }

        // Split packet
        if (substr($data, 0, 4) === self::HEADER_SPLIT) {
            return $this->readSplitResponse($socket, $data, $maxSize);
        }

        return $data;
    }

    /**
     * Assemble multi-packet (split) response.
     * Handles both Source (12-byte header) and GoldSrc (9-byte header) formats.
     */
    private function readSplitResponse($socket, string $firstPacket, int $maxSize): string
    {
        $packets = [];
        $packet = $firstPacket;
        $totalPackets = null;
        $isCompressed = false;
        $checksum = null;

        do {
            // Minimum: 4 (split header) + 4 (request ID) + 1 (fragment byte) = 9 bytes
            if (strlen($packet) < 9) {
                break;
            }

            $requestId = unpack('V', substr($packet, 4, 4))[1];
            $isCompressed = ( $requestId & 0x80000000 ) !== 0;

            // Detect GoldSrc vs Source split format.
            // GoldSrc: byte[8] = (upper nibble: total, lower nibble: number), payload at offset 9
            // Source: byte[8] = total count, byte[9] = packet number, payload at offset 12+
            $isGoldSrc = $this->isGoldSrcSplit($packet);

            if ($isGoldSrc) {
                $byte8 = ord($packet[8]);
                $totalPackets = ( $byte8 >> 4 ) & 0x0F;
                $packetNumber = $byte8 & 0x0F;
                $payload = substr($packet, 9);
            } else {
                if (strlen($packet) < 12) {
                    break;
                }

                $totalPackets = ord($packet[8]);
                $packetNumber = ord($packet[9]);

                if ($isCompressed && strlen($packet) >= 18) {
                    $checksum = unpack('V', substr($packet, 14, 4))[1];
                    $payload = substr($packet, 18);
                } else {
                    $payload = substr($packet, 12);
                }
            }

            $packets[$packetNumber] = $payload;

            if ($totalPackets !== null && $totalPackets > 0 && count($packets) >= $totalPackets) {
                break;
            }

            $packet = $this->readWithTimeout($socket, $maxSize);
        } while ($packet !== '' && substr($packet, 0, 4) === self::HEADER_SPLIT);

        if ($totalPackets === null || $totalPackets === 0 || count($packets) !== $totalPackets) {
            return $firstPacket;
        }

        ksort($packets);
        $data = implode('', $packets);

        if ($isCompressed && !$isGoldSrc) {
            if (!function_exists('bzdecompress')) {
                throw new RuntimeException('Split packet is BZip2 compressed but bz2 extension is not installed');
            }

            $data = bzdecompress($data);

            if (!is_string($data)) {
                throw new RuntimeException('Failed to decompress BZip2 split packet');
            }

            if ($checksum !== null && crc32($data) !== $checksum) {
                throw new RuntimeException('CRC32 mismatch on decompressed split packet');
            }
        }

        return self::HEADER_SINGLE . $data;
    }

    /**
     * Detect whether a split packet uses GoldSrc format.
     *
     * GoldSrc packs total and number into a single byte at offset 8.
     * Source uses separate bytes at offsets 8 (total) and 9 (number).
     * Heuristic: if byte[8] upper nibble > 0 AND lower nibble < upper nibble,
     * it's likely GoldSrc. Also, GoldSrc never has more than 15 fragments.
     */
    private function isGoldSrcSplit(string $packet): bool
    {
        if (strlen($packet) < 10) {
            return true; // Too short for Source format, assume GoldSrc
        }

        $byte8 = ord($packet[8]);
        $byte9 = ord($packet[9]);

        $gsTotal = ( $byte8 >> 4 ) & 0x0F;
        $gsNumber = $byte8 & 0x0F;

        // Source format: byte[8] is total count (typically 2-10), byte[9] is current (0-based)
        // GoldSrc format: byte[8] has both packed, byte[9] is start of payload

        // If packed total is valid (1-15) and number < total, likely GoldSrc
        // But Source total can also be small (2-5), so also check:
        // In Source, there's a 2-byte "max packet size" at bytes 10-11 (usually 0x04E0 = 1248)
        if (strlen($packet) >= 12) {
            $maxSize = unpack('v', substr($packet, 10, 2))[1];

            // Source engines typically report 1200-1260 as max packet size
            if ($maxSize >= 1000 && $maxSize <= 1400) {
                return false; // Source format
            }
        }

        // Fallback: if upper nibble encodes a reasonable total, it's GoldSrc
        return $gsTotal > 0 && $gsTotal <= 15 && $gsNumber < $gsTotal;
    }

    /**
     * Extract response type and payload from a raw packet.
     *
     * @return array{0: int|null, 1: string}
     */
    private function parsePacket(string $raw): array
    {
        if ($raw === '' || strlen($raw) < 5) {
            return [null, ''];
        }

        if (substr($raw, 0, 4) === self::HEADER_SINGLE) {
            return [ord($raw[4]), substr($raw, 5)];
        }

        return [ord($raw[0]), substr($raw, 1)];
    }

    private function readCString(string $data, int &$offset): string
    {
        if ($offset >= strlen($data)) {
            return '';
        }

        $pos = strpos($data, "\x00", $offset);

        if ($pos === false) {
            $str = substr($data, $offset);
            $offset = strlen($data);

            return $str;
        }

        $str = substr($data, $offset, $pos - $offset);
        $offset = $pos + 1;

        return $str;
    }

    private function readUint8(string $data, int &$offset): int
    {
        if ($offset >= strlen($data)) {
            return 0;
        }

        return ord($data[$offset++]);
    }

    private function readUint16(string $data, int &$offset): int
    {
        if (( $offset + 2 ) > strlen($data)) {
            return 0;
        }

        $val = unpack('v', substr($data, $offset, 2))[1];
        $offset += 2;

        return $val;
    }

    private function readUint32(string $data, int &$offset): int
    {
        if (( $offset + 4 ) > strlen($data)) {
            return 0;
        }

        $val = unpack('V', substr($data, $offset, 4))[1];
        $offset += 4;

        return $val;
    }

    private function readUint64(string $data, int &$offset): string
    {
        if (( $offset + 8 ) > strlen($data)) {
            return '0';
        }

        $low = unpack('V', substr($data, $offset, 4))[1];
        $high = unpack('V', substr($data, $offset + 4, 4))[1];
        $offset += 8;

        // Return as string to avoid 32-bit overflow
        return bcadd(bcmul((string) $high, '4294967296'), (string) $low);
    }

    /**
     * Read from UDP socket with timeout.
     * Uses stream_select as primary timeout (works on all platforms),
     * with stream_set_timeout as fallback safety net.
     */
    private function readWithTimeout($socket, int $maxSize): string
    {
        $read = [$socket];
        $write = null;
        $except = null;

        // stream_select: reliable timeout for UDP on Windows/Linux/Mac
        $ready = @stream_select($read, $write, $except, $this->readTimeout);

        if ($ready === false || $ready === 0) {
            return '';
        }

        $data = @fread($socket, $maxSize);

        return $data === false || $data === '' ? '' : $data;
    }
}
