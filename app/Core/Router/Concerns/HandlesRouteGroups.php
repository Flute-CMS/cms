<?php

namespace Flute\Core\Router\Concerns;

trait HandlesRouteGroups
{
    /**
     * Create a group of routes with middleware.
     */
    public function group(array|callable $attributes, ?callable $callback = null): void
    {
        if (is_callable($attributes)) {
            $callback = $attributes;
            $attributes = [];
        }

        $this->updateGroupStack($attributes);

        $callback($this);

        array_pop($this->groupStack);
    }

    protected function updateGroupStack(array $attributes): self
    {
        $this->groupStack[] = $this->mergeGroupAttributes($attributes);

        return $this;
    }

    protected function mergeGroupAttributes(array $new): array
    {
        $old = $this->groupStack ? end($this->groupStack) : [];

        $new['prefix'] = isset($old['prefix'])
            ? trim($old['prefix'], '/') . '/' . trim($new['prefix'] ?? '', '/')
            : $new['prefix'] ?? '';

        if (isset($old['middleware'])) {
            $middleware = array_merge((array) $old['middleware'], (array) ( $new['middleware'] ?? [] ));
            $new['middleware'] = $middleware;
        }

        if (isset($old['excluded_middleware'])) {
            $excludedMiddleware = array_merge(
                (array) $old['excluded_middleware'],
                (array) ( $new['excluded_middleware'] ?? [] ),
            );
            $new['excluded_middleware'] = $excludedMiddleware;
        }

        return array_merge($old, $new);
    }

    protected function getGroupAttribute(string $key, $default = null)
    {
        return $this->groupStack ? $this->groupStack[count($this->groupStack) - 1][$key] ?? $default : $default;
    }
}
