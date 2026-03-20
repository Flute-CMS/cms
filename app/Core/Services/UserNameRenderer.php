<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\User;
use Throwable;

class UserNameRenderer
{
    public const POSITION_BEFORE = 'before';

    public const POSITION_AFTER = 'after';

    public const PRIORITY_HIGH = 100;

    public const PRIORITY_NORMAL = 50;

    public const PRIORITY_LOW = 10;

    /**
     * Global decorators applied to all users.
     * Each entry: ['callback' => callable, 'position' => string, 'priority' => int]
     *
     * @var array<int, array{callback: callable, position: string, priority: int}>
     */
    protected array $globalDecorators = [];

    /**
     * Per-user decorators keyed by user ID.
     *
     * @var array<int, array<int, array{callback: callable, position: string, priority: int}>>
     */
    protected array $userDecorators = [];

    /**
     * Name transformers that modify the name text itself.
     *
     * @var array<int, array{callback: callable, priority: int}>
     */
    protected array $nameTransformers = [];

    /**
     * Register a global decorator that applies to every user.
     *
     * The callback receives (User $user) and must return an HTML string (or empty string to skip).
     */
    public function addGlobalDecorator(
        callable $callback,
        string $position = self::POSITION_AFTER,
        int $priority = self::PRIORITY_NORMAL,
    ): self {
        $this->globalDecorators[] = [
            'callback' => $callback,
            'position' => $position,
            'priority' => $priority,
        ];

        return $this;
    }

    /**
     * Register a decorator for a specific user.
     *
     * The callback receives (User $user) and must return an HTML string (or empty string to skip).
     */
    public function addUserDecorator(
        int $userId,
        callable $callback,
        string $position = self::POSITION_AFTER,
        int $priority = self::PRIORITY_NORMAL,
    ): self {
        $this->userDecorators[$userId][] = [
            'callback' => $callback,
            'position' => $position,
            'priority' => $priority,
        ];

        return $this;
    }

    /**
     * Register a name transformer that can modify the name text itself.
     *
     * The callback receives (string $name, User $user) and must return the modified name string.
     */
    public function addNameTransformer(callable $callback, int $priority = self::PRIORITY_NORMAL): self
    {
        $this->nameTransformers[] = [
            'callback' => $callback,
            'priority' => $priority,
        ];

        return $this;
    }

    /**
     * Render a fully decorated username as HTML.
     *
     * @param User   $user     The user entity
     * @param string $class    Additional CSS class for the wrapper
     * @param bool   $withColor Whether to apply role color to the name
     * @param bool   $link     Whether to wrap name in a profile link
     */
    public function render(User $user, string $class = '', bool $withColor = true, bool $link = false): string
    {
        $name = $this->transformName($user->name, $user);

        $decorators = $this->collectDecorators($user);

        $beforeHtml = $this->renderDecorators($decorators, self::POSITION_BEFORE, $user);
        $afterHtml = $this->renderDecorators($decorators, self::POSITION_AFTER, $user);

        $roleColor = $withColor ? $this->getUserRoleColor($user) : null;
        $colorStyle = $roleColor ? " style=\"color: {$roleColor}\"" : '';
        $escapedName = e($name);

        $nameHtml = "<span class=\"user-name__text\"{$colorStyle}>{$escapedName}</span>";

        if ($link) {
            $url = url('profile/' . $user->getUrl());
            $nameHtml = "<a href=\"{$url}\" class=\"user-name__link\"{$colorStyle}>{$escapedName}</a>";
        }

        $wrapperClass = 'user-name' . ( $class ? ' ' . e($class) : '' );

        return "<span class=\"{$wrapperClass}\">{$beforeHtml}{$nameHtml}{$afterHtml}</span>";
    }

    /**
     * Get just the plain text name after transformers (no HTML).
     */
    public function getPlainName(User $user): string
    {
        return $this->transformName($user->name, $user);
    }

    /**
     * Get only the decorator HTML for a given position.
     */
    public function getDecoratorsHtml(User $user, string $position = self::POSITION_AFTER): string
    {
        $decorators = $this->collectDecorators($user);

        return $this->renderDecorators($decorators, $position, $user);
    }

    protected function transformName(string $name, User $user): string
    {
        $transformers = $this->nameTransformers;
        usort($transformers, static fn($a, $b) => $b['priority'] <=> $a['priority']);

        foreach ($transformers as $transformer) {
            $name = $transformer['callback']($name, $user);
        }

        return $name;
    }

    protected function collectDecorators(User $user): array
    {
        $decorators = $this->globalDecorators;

        if (isset($this->userDecorators[$user->id])) {
            $decorators = array_merge($decorators, $this->userDecorators[$user->id]);
        }

        usort($decorators, static fn($a, $b) => $b['priority'] <=> $a['priority']);

        return $decorators;
    }

    protected function renderDecorators(array $decorators, string $position, User $user): string
    {
        $html = '';

        foreach ($decorators as $decorator) {
            if ($decorator['position'] !== $position) {
                continue;
            }

            try {
                $result = $decorator['callback']($user);
                if ($result !== null && $result !== '') {
                    $html .= $result;
                }
            } catch (Throwable $e) {
                if (function_exists('logs')) {
                    logs()->error('UserNameRenderer decorator error: ' . $e->getMessage());
                }
            }
        }

        return $html;
    }

    protected function getUserRoleColor(User $user): ?string
    {
        $maxPriorityRole = null;
        $maxPriority = -1;

        foreach ($user->roles as $role) {
            if ($role->priority > $maxPriority) {
                $maxPriority = $role->priority;
                $maxPriorityRole = $role;
            }
        }

        return $maxPriorityRole?->color;
    }
}
