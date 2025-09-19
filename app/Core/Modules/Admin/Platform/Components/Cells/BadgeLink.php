<?php

namespace Flute\Admin\Platform\Components\Cells;

use Closure;
use Illuminate\View\Component;

class BadgeLink extends Component
{
    /**
     * Display value (badge text).
     */
    public string $value;

    /**
     * URL link.
     */
    public string $url;

    /**
     * Row data.
     */
    public object $_row;

    /**
     * CSS‑class for badge.
     */
    public string $badgeClass;

    /**
     * BadgeLink constructor.
     *
     * @param string $value      Cell value (will be substituted in badge text).
     * @param string|Closure $url Link address. If a string is passed and it contains a placeholder `%s`, it will be replaced with the value.
     *                             If a closure is passed, it will be called with the value as an argument.
     * @param string $badgeClass CSS‑class for badge (default is «badge-primary»).
     */
    public function __construct(string $value, $_row, $url, string $badgeClass = 'primary')
    {
        $this->value = $value;
        $this->_row = $_row;

        if (is_callable($url)) {
            $url = call_user_func($url, $value);
        }

        if (strpos($url, '%s') !== false) {
            $url = sprintf($url, $value);
        }

        foreach ($this->_row as $key => $value) {
            if (!is_object($value) && !is_array($value)) {
                $url = str_replace(":{$key}", (string) $value, $url);
            }

            if (is_object($value) || is_array($value)) {
                $parts = explode('.', $key);
                if (count($parts) > 1) {
                    $current = $value;
                    $path = '';
                    foreach ($parts as $part) {
                        $path .= ($path ? '.' : '') . $part;
                        if (is_object($current) && isset($current->$part)) {
                            $current = $current->$part;
                        } elseif (is_array($current) && isset($current[$part])) {
                            $current = $current[$part];
                        }
                    }
                    if (!is_object($current) && !is_array($current)) {
                        $url = str_replace(":{$path}", (string) $current, $url);
                    }
                } else {
                    $this->processNestedValue($value, $key, $url);
                }
            }
        }

        $this->url = $url;
        $this->badgeClass = $badgeClass;
    }

    /**
     * Generates HTML for the component.
     *
     * @return string
     */
    public function render()
    {
        if (str_contains((string)$this->url, 'http')) {
            return sprintf(
                '<a href="%s" class="badge %s" target="_blank">%s</a>',
                $this->url,
                htmlspecialchars($this->badgeClass, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($this->value, ENT_QUOTES, 'UTF-8')
            );
        }

        return sprintf(
            '<a href="%s" class="badge %s" hx-include="none" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">%s</a>',
            $this->url,
            htmlspecialchars($this->badgeClass, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->value, ENT_QUOTES, 'UTF-8')
        );
    }

    private function processNestedValue($value, $key, $url)
    {
        if (is_object($value) && isset($value->{$key})) {
            $url = str_replace(":{$key}", $value->{$key}, $url);
        }
    }
}
