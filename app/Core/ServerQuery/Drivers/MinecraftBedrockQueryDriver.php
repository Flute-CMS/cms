<?php

namespace Flute\Core\ServerQuery\Drivers;

use Flute\Core\ServerQuery\QueryDriverInterface;
use Flute\Core\ServerQuery\QueryResult;

/**
 * Minecraft Bedrock Edition - RakNet Unconnected Ping.
 *
 * @link https://wiki.bedrock.dev/servers/raknet
 */
class MinecraftBedrockQueryDriver implements QueryDriverInterface
{
    private const RAKNET_MAGIC = "\x00\xFF\xFF\x00\xFE\xFE\xFE\xFE\xFD\xFD\xFD\xFD\x12\x34\x56\x78";

    private const UNCONNECTED_PING = 0x01;

    private const UNCONNECTED_PONG = 0x1C;

    public function query(string $ip, int $port, int $timeout = 3, array $settings = []): QueryResult
    {
        $result = new QueryResult();
        $result->game = 'minecraft_bedrock';

        $socket = @stream_socket_client("udp://{$ip}:{$port}", $errno, $errstr, $timeout);

        if (!$socket) {
            return $result;
        }

        stream_set_timeout($socket, $timeout);

        try {
            // Build Unconnected Ping packet
            $packet = chr(self::UNCONNECTED_PING);
            $packet .= pack('J', (int) ( microtime(true) * 1000 )); // Client alive time (uint64 BE)
            $packet .= self::RAKNET_MAGIC;
            $packet .= pack('J', mt_rand()); // Client GUID (int64 BE)

            fwrite($socket, $packet);

            $response = @fread($socket, 4096);

            if ($response === false || strlen($response) < 35) {
                return $result;
            }

            // Verify packet type
            if (ord($response[0]) !== self::UNCONNECTED_PONG) {
                return $result;
            }

            // Skip: pong type (1) + client time (8) + server GUID (8) + magic (16) = 33 bytes
            $offset = 33;

            // Read server string length (uint16 BE)
            if (( $offset + 2 ) > strlen($response)) {
                return $result;
            }

            $stringLength = unpack('n', substr($response, $offset, 2))[1];
            $offset += 2;

            if (( $offset + $stringLength ) > strlen($response)) {
                return $result;
            }

            $serverString = substr($response, $offset, $stringLength);
            $parts = explode(';', $serverString);

            // Format: Edition;MOTD1;Protocol;Version;Players;MaxPlayers;ServerID;MOTD2;Gamemode;GamemodeNum;PortIPv4;PortIPv6
            if (count($parts) < 6) {
                return $result;
            }

            $result->online = true;
            $result->hostname = $parts[1] ?? null;
            $result->players = (int) ( $parts[4] ?? 0 );
            $result->maxPlayers = (int) ( $parts[5] ?? 0 );
            $result->version = $parts[3] ?? null;
            $result->map = $parts[7] ?? 'Overworld'; // MOTD line 2, often used for map/subtitle

            $result->additional = [
                'edition' => $parts[0] ?? null, // MCPE or MCEE
                'protocol_version' => (int) ( $parts[2] ?? 0 ),
                'server_id' => $parts[6] ?? null,
                'gamemode' => $parts[8] ?? null,
                'gamemode_numeric' => (int) ( $parts[9] ?? 0 ),
                'port_ipv4' => (int) ( $parts[10] ?? 0 ),
                'port_ipv6' => (int) ( $parts[11] ?? 0 ),
            ];
        } finally {
            fclose($socket);
        }

        return $result;
    }
}
