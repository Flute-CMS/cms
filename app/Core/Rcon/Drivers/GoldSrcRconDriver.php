<?php

namespace Flute\Core\Rcon\Drivers;

use Flute\Core\Rcon\RconDriverInterface;
use RuntimeException;
use xPaw\SourceQuery\Exception\AuthenticationException;
use xPaw\SourceQuery\Exception\InvalidPacketException;
use xPaw\SourceQuery\Exception\SocketException;
use xPaw\SourceQuery\SourceQuery;

/**
 * GoldSrc RCON driver (UDP).
 *
 * Works with Counter-Strike 1.6, Half-Life and other GoldSrc engine games.
 *
 * @link https://developer.valvesoftware.com/wiki/GoldSrc_RCON_Protocol
 */
class GoldSrcRconDriver implements RconDriverInterface
{
    public function execute(string $ip, int $port, string $password, string $command, int $timeout = 3): string
    {
        $query = new SourceQuery();

        try {
            $query->Connect($ip, $port, $timeout, SourceQuery::GOLDSOURCE);
            $query->SetRconPassword($password);

            $result = $query->Rcon($command);

            return $result ?: '';
        } catch (AuthenticationException $e) {
            throw new RuntimeException('RCON auth failed: ' . $e->getMessage(), 0, $e);
        } catch (SocketException|InvalidPacketException $e) {
            throw new RuntimeException("RCON connection failed to {$ip}:{$port}: " . $e->getMessage(), 0, $e);
        } finally {
            $query->Disconnect();
        }
    }

    public function test(string $ip, int $port, string $password, int $timeout = 3): bool
    {
        try {
            $this->execute($ip, $port, $password, 'status', $timeout);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }
}
