<?php

namespace Flute\Core\Rcon;

interface RconDriverInterface
{
    /**
     * Execute an RCON command on a game server.
     *
     * @param string $ip Server IP address
     * @param int $port RCON port
     * @param string $password RCON password
     * @param string $command Command to execute
     * @param int $timeout Connection timeout in seconds
     *
     * @return string Command output
     *
     * @throws \RuntimeException On connection or auth failure
     */
    public function execute(string $ip, int $port, string $password, string $command, int $timeout = 3): string;

    /**
     * Test if RCON connection and authentication works.
     */
    public function test(string $ip, int $port, string $password, int $timeout = 3): bool;
}
