<?php

namespace Flute\Admin\Packages\Search\Services;

class SlashCommandsRegistry
{
    private static array $commands = [];

    /**
     * Register a slash command
     *
     * @param string $command Command without the leading slash (e.g. 'user')
     * @param string $description Description of the command
     * @param string $icon Icon path
     * @return void
     */
    public static function register(string $command, string $description, string $icon = ''): void
    {
        self::$commands[$command] = [
            'command' => '/' . $command,
            'description' => $description,
            'icon' => $icon
        ];
    }

    /**
     * Get all registered commands
     *
     * @return array
     */
    public static function all(): array
    {
        return array_values(self::$commands);
    }

    /**
     * Get a specific command
     *
     * @param string $command Command without the leading slash
     * @return array|null
     */
    public static function get(string $command): ?array
    {
        return self::$commands[$command] ?? null;
    }
} 