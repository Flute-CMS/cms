<?php

namespace Flute\Core\ServerQuery;

class QueryResult
{
    public bool $online = false;

    public int $players = 0;

    public int $maxPlayers = 0;

    public ?string $hostname = null;

    public ?string $map = null;

    public ?string $game = null;

    public ?string $version = null;

    /** @var array<int, array{name: string, score: int, time: float}> */
    public array $playersData = [];

    /** @var array<string, mixed> */
    public array $additional = [];
}
