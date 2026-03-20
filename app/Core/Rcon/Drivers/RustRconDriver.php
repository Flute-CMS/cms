<?php

namespace Flute\Core\Rcon\Drivers;

use Flute\Core\Rcon\RconDriverInterface;
use RuntimeException;

/**
 * Rust WebSocket RCON Protocol driver.
 *
 * Rust uses JSON over WebSocket instead of the binary Source RCON protocol.
 * Password is passed in the WebSocket URL path.
 *
 * Requires: ext-sockets or stream_socket_client
 *
 * @link https://github.com/Facepunch/webrcon
 */
class RustRconDriver implements RconDriverInterface
{
    public function execute(string $ip, int $port, string $password, string $command, int $timeout = 3): string
    {
        $socket = $this->connect($ip, $port, $password, $timeout);

        try {
            $identifier = mt_rand(1, 999999);

            $payload = json_encode([
                'Identifier' => $identifier,
                'Message' => $command,
                'Name' => 'WebRcon',
            ]);

            $this->sendWebSocketFrame($socket, $payload);

            // Read responses until we find ours
            $deadline = microtime(true) + $timeout;

            while (microtime(true) < $deadline) {
                $frame = $this->readWebSocketFrame($socket);

                if ($frame === null) {
                    break;
                }

                $response = json_decode($frame, true);

                if (!is_array($response)) {
                    continue;
                }

                if (( $response['Identifier'] ?? 0 ) === $identifier) {
                    return $response['Message'] ?? '';
                }
            }

            return '';
        } finally {
            fclose($socket);
        }
    }

    public function test(string $ip, int $port, string $password, int $timeout = 3): bool
    {
        try {
            $socket = $this->connect($ip, $port, $password, $timeout);
            fclose($socket);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * @return resource
     */
    private function connect(string $ip, int $port, string $password, int $timeout)
    {
        $socket = @stream_socket_client("tcp://{$ip}:{$port}", $errno, $errstr, $timeout);

        if (!$socket) {
            throw new RuntimeException("Rust RCON connection failed: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $timeout);

        // WebSocket handshake
        $key = base64_encode(random_bytes(16));
        $path = '/' . rawurlencode($password);

        $headers =
            "GET {$path} HTTP/1.1\r\n"
            . "Host: {$ip}:{$port}\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Key: {$key}\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . "\r\n";

        fwrite($socket, $headers);

        $response = '';
        $deadline = microtime(true) + $timeout;

        while (microtime(true) < $deadline) {
            $line = fgets($socket, 4096);

            if ($line === false || $line === '') {
                break;
            }

            $response .= $line;

            if ($line === "\r\n") {
                break;
            }
        }

        if (!str_contains($response, '101')) {
            fclose($socket);

            throw new RuntimeException('Rust RCON WebSocket upgrade failed');
        }

        return $socket;
    }

    private function sendWebSocketFrame($socket, string $data): void
    {
        $length = strlen($data);
        $frame = "\x81"; // Text frame, FIN bit set

        // Client frames must be masked
        if ($length < 126) {
            $frame .= chr($length | 0x80);
        } elseif ($length < 65536) {
            $frame .= chr(126 | 0x80) . pack('n', $length);
        } else {
            $frame .= chr(127 | 0x80) . pack('J', $length);
        }

        // Masking key
        $mask = random_bytes(4);
        $frame .= $mask;

        // Mask the data
        for ($i = 0; $i < $length; $i++) {
            $frame .= $data[$i] ^ $mask[$i % 4];
        }

        fwrite($socket, $frame);
    }

    private function readWebSocketFrame($socket): ?string
    {
        $header = fread($socket, 2);

        if ($header === false || strlen($header) < 2) {
            return null;
        }

        $firstByte = ord($header[0]);
        $secondByte = ord($header[1]);

        $opcode = $firstByte & 0x0F;

        // Close frame
        if ($opcode === 0x08) {
            return null;
        }

        $masked = ( $secondByte & 0x80 ) !== 0;
        $length = $secondByte & 0x7F;

        if ($length === 126) {
            $extLen = fread($socket, 2);

            if ($extLen === false || strlen($extLen) < 2) {
                return null;
            }

            $length = unpack('n', $extLen)[1];
        } elseif ($length === 127) {
            $extLen = fread($socket, 8);

            if ($extLen === false || strlen($extLen) < 8) {
                return null;
            }

            $length = unpack('J', $extLen)[1];
        }

        $mask = null;

        if ($masked) {
            $mask = fread($socket, 4);

            if ($mask === false || strlen($mask) < 4) {
                return null;
            }
        }

        $data = '';
        $remaining = $length;

        while ($remaining > 0) {
            $chunk = fread($socket, min($remaining, 8192));

            if ($chunk === false || $chunk === '') {
                return null;
            }

            $data .= $chunk;
            $remaining -= strlen($chunk);
        }

        if ($mask !== null) {
            for ($i = 0; $i < $length; $i++) {
                $data[$i] ^= $mask[$i % 4];
            }
        }

        return $data;
    }
}
