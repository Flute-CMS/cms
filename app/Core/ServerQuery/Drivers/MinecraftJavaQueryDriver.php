<?php

namespace Flute\Core\ServerQuery\Drivers;

use Flute\Core\ServerQuery\QueryDriverInterface;
use Flute\Core\ServerQuery\QueryResult;

/**
 * Minecraft Java Edition - Server List Ping (SLP) protocol.
 *
 * Uses the modern TCP-based protocol (1.7+) that works on every server
 * without requiring enable-query=true in server.properties.
 *
 * @link https://minecraft.wiki/w/Java_Edition_protocol/Server_List_Ping
 */
class MinecraftJavaQueryDriver implements QueryDriverInterface
{
    public function query(string $ip, int $port, int $timeout = 3, array $settings = []): QueryResult
    {
        $result = new QueryResult();
        $result->game = 'minecraft';

        $socket = @stream_socket_client("tcp://{$ip}:{$port}", $errno, $errstr, $timeout);

        if (!$socket) {
            return $result;
        }

        stream_set_timeout($socket, $timeout);

        try {
            // Step 1: Handshake packet (packet ID 0x00)
            $handshake = $this->buildHandshakePacket($ip, $port);
            fwrite($socket, $handshake);

            // Step 2: Status Request packet (packet ID 0x00, no fields)
            fwrite($socket, $this->packVarIntPacket("\x00"));

            // Step 3: Read Status Response
            $json = $this->readStatusResponse($socket);

            if ($json === null) {
                return $result;
            }

            $data = json_decode($json, true);

            if (!is_array($data)) {
                return $result;
            }

            $result->online = true;
            $result->players = $data['players']['online'] ?? 0;
            $result->maxPlayers = $data['players']['max'] ?? 0;
            $result->version = $data['version']['name'] ?? null;
            $result->hostname = $this->extractMotd($data['description'] ?? '');
            $result->map = 'Overworld';

            // Player sample — filter out fake/advertisement entries
            if (!empty($data['players']['sample']) && is_array($data['players']['sample'])) {
                foreach ($data['players']['sample'] as $player) {
                    $name = $this->stripMinecraftFormatting($player['name'] ?? '');
                    $uuid = $player['id'] ?? '';

                    // Skip empty names, formatting-only entries, and fake UUIDs (all zeros)
                    if ($name === '' || $uuid === '00000000-0000-0000-0000-000000000000') {
                        continue;
                    }

                    $result->playersData[] = [
                        'name' => $name,
                        'score' => 0,
                        'time' => 0,
                    ];
                }
            }

            // Store full response in additional for modules that need extra data
            $result->additional = $data;

            // Remove favicon from additional (can be huge)
            unset($result->additional['favicon']);
        } finally {
            fclose($socket);
        }

        return $result;
    }

    private function buildHandshakePacket(string $host, int $port): string
    {
        $data = "\x00"; // Packet ID
        $data .= $this->packVarInt(-1); // Protocol version (-1 = detect)
        $data .= $this->packString($host); // Server address
        $data .= pack('n', $port); // Server port (unsigned short, big-endian)
        $data .= $this->packVarInt(1); // Next state: 1 = Status

        return $this->packVarIntPacket($data);
    }

    private function readStatusResponse($socket): ?string
    {
        // Read packet length
        $packetLength = $this->readVarInt($socket);

        if ($packetLength === null || $packetLength < 1) {
            return null;
        }

        // Read packet ID
        $packetId = $this->readVarInt($socket);

        if ($packetId !== 0) {
            return null;
        }

        // Read JSON string length
        $jsonLength = $this->readVarInt($socket);

        if ($jsonLength === null || $jsonLength < 2) {
            return null;
        }

        // Read JSON data
        $json = '';
        $remaining = $jsonLength;

        while ($remaining > 0) {
            $chunk = fread($socket, min($remaining, 8192));

            if ($chunk === false || $chunk === '') {
                return null;
            }

            $json .= $chunk;
            $remaining -= strlen($chunk);
        }

        return $json;
    }

    /**
     * Extract plain text MOTD from description field.
     * Description can be a string or a chat component object.
     */
    private function extractMotd(mixed $description): string
    {
        if (is_string($description)) {
            return $this->stripMinecraftFormatting($description);
        }

        if (is_array($description)) {
            $text = $description['text'] ?? '';

            if (!empty($description['extra']) && is_array($description['extra'])) {
                foreach ($description['extra'] as $extra) {
                    if (is_array($extra) && isset($extra['text'])) {
                        $text .= $extra['text'];
                    } elseif (is_string($extra)) {
                        $text .= $extra;
                    }
                }
            }

            return $this->stripMinecraftFormatting($text);
        }

        return '';
    }

    private function stripMinecraftFormatting(string $text): string
    {
        return preg_replace('/§[0-9a-fk-or]/i', '', $text);
    }

    private function readVarInt($socket): ?int
    {
        $value = 0;
        $size = 0;

        do {
            $byte = fread($socket, 1);

            if ($byte === false || $byte === '') {
                return null;
            }

            $byte = ord($byte);
            $value |= ( $byte & 0x7F ) << ( $size * 7 );
            $size++;

            if ($size > 5) {
                return null; // VarInt too big
            }
        } while (( $byte & 0x80 ) !== 0);

        // Handle negative VarInt (two's complement for 32-bit)
        if ($value >= 0x80000000) {
            $value -= 0x100000000;
        }

        return $value;
    }

    private function packVarInt(int $value): string
    {
        // Handle negative values (two's complement for 32-bit)
        if ($value < 0) {
            $value += 0x100000000;
        }

        $result = '';

        do {
            $byte = $value & 0x7F;
            $value >>= 7;

            if ($value !== 0) {
                $byte |= 0x80;
            }

            $result .= chr($byte);
        } while ($value !== 0);

        return $result;
    }

    private function packString(string $str): string
    {
        return $this->packVarInt(strlen($str)) . $str;
    }

    /**
     * Wrap data in a VarInt-length-prefixed packet.
     */
    private function packVarIntPacket(string $data): string
    {
        return $this->packVarInt(strlen($data)) . $data;
    }
}
