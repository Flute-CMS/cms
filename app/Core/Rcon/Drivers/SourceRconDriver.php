<?php

namespace Flute\Core\Rcon\Drivers;

use Flute\Core\Rcon\RconDriverInterface;
use RuntimeException;

/**
 * Source RCON Protocol driver.
 *
 * Works with all Source engine games (CS2, CS:GO, CSS, TF2, etc.)
 * and Minecraft Java Edition (same TCP RCON protocol).
 *
 * @link https://developer.valvesoftware.com/wiki/Source_RCON_Protocol
 * @link https://minecraft.wiki/w/RCON
 */
class SourceRconDriver implements RconDriverInterface
{
    private const SERVERDATA_AUTH = 3;

    private const SERVERDATA_AUTH_RESPONSE = 2;

    private const SERVERDATA_EXECCOMMAND = 2;

    private const SERVERDATA_RESPONSE_VALUE = 0;

    private const MAX_PAYLOAD_SIZE = 4096;

    public function execute(string $ip, int $port, string $password, string $command, int $timeout = 3): string
    {
        $socket = $this->connect($ip, $port, $timeout);

        try {
            $this->authenticate($socket, $password);

            return $this->sendCommand($socket, $command);
        } finally {
            fclose($socket);
        }
    }

    public function test(string $ip, int $port, string $password, int $timeout = 3): bool
    {
        try {
            $socket = $this->connect($ip, $port, $timeout);

            try {
                $this->authenticate($socket, $password);

                return true;
            } finally {
                fclose($socket);
            }
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * @return resource
     */
    private function connect(string $ip, int $port, int $timeout)
    {
        $socket = @fsockopen('tcp://' . $ip, $port, $errno, $errstr, $timeout);

        if (!$socket) {
            throw new RuntimeException("RCON connection failed to {$ip}:{$port}: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $timeout);

        return $socket;
    }

    private function authenticate($socket, string $password): void
    {
        $requestId = 1;

        $this->writePacket($socket, $requestId, self::SERVERDATA_AUTH, $password);

        // Some servers send an empty RESPONSE_VALUE before AUTH_RESPONSE
        $response = $this->readPacket($socket);

        if ($response === null) {
            throw new RuntimeException('RCON auth failed: no response from server');
        }

        // If we got a RESPONSE_VALUE, read the actual AUTH_RESPONSE
        if ($response['type'] === self::SERVERDATA_RESPONSE_VALUE) {
            $response = $this->readPacket($socket);

            if ($response === null) {
                throw new RuntimeException('RCON auth failed: no auth response from server');
            }
        }

        if ($response['id'] === -1) {
            throw new RuntimeException('RCON auth failed: invalid password');
        }
    }

    private function sendCommand($socket, string $command): string
    {
        $commandId = 2;
        $endMarker = 3;

        $this->writePacket($socket, $commandId, self::SERVERDATA_EXECCOMMAND, $command);

        // Send an empty RESPONSE_VALUE as end-of-response marker
        $this->writePacket($socket, $endMarker, self::SERVERDATA_RESPONSE_VALUE, '');

        $output = '';

        while (true) {
            $response = $this->readPacket($socket);

            if ($response === null) {
                break;
            }

            // When we receive the response to our end-marker, we're done
            if ($response['id'] === $endMarker) {
                break;
            }

            if ($response['id'] === $commandId) {
                $output .= $response['body'];
            }
        }

        return $output;
    }

    private function writePacket($socket, int $id, int $type, string $body): void
    {
        $payload = pack('VV', $id, $type) . $body . "\x00\x00";
        $packet = pack('V', strlen($payload)) . $payload;

        fwrite($socket, $packet);
    }

    /**
     * @return array{id: int, type: int, body: string}|null
     */
    private function readPacket($socket): ?array
    {
        // Read packet size (4 bytes, int32 LE)
        $sizeData = $this->readExact($socket, 4);

        if ($sizeData === null) {
            return null;
        }

        $size = unpack('V', $sizeData)[1];

        if ($size < 10 || $size > ( self::MAX_PAYLOAD_SIZE + 10 )) {
            return null;
        }

        $data = $this->readExact($socket, $size);

        if ($data === null) {
            return null;
        }

        $id = unpack('V', substr($data, 0, 4))[1];

        // Handle signed int32 for auth failure (-1)
        if ($id >= 0x80000000) {
            $id -= 0x100000000;
        }

        $type = unpack('V', substr($data, 4, 4))[1];
        $body = substr($data, 8, -2); // Strip two null terminators

        return [
            'id' => $id,
            'type' => $type,
            'body' => $body,
        ];
    }

    private function readExact($socket, int $length): ?string
    {
        $data = '';
        $remaining = $length;

        while ($remaining > 0) {
            $chunk = fread($socket, $remaining);

            if ($chunk === false || $chunk === '') {
                return null;
            }

            $data .= $chunk;
            $remaining -= strlen($chunk);
        }

        return $data;
    }
}
