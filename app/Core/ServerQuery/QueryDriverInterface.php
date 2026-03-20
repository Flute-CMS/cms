<?php

namespace Flute\Core\ServerQuery;

interface QueryDriverInterface
{
    /**
     * Query a game server and return standardized result.
     *
     * @param string $ip Server IP address
     * @param int $port Server port (game port)
     * @param int $timeout Connection timeout in seconds
     * @param array<string, mixed> $settings Additional settings (query_port, rcon_port, etc.)
     */
    public function query(string $ip, int $port, int $timeout = 3, array $settings = []): QueryResult;
}
