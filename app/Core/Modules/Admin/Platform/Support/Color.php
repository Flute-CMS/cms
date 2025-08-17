<?php

namespace Flute\Admin\Platform\Support;

/**
 * This class represents a list of colors.
 */
enum Color
{
    // All available colors
    case INFO;
    case SUCCESS;
    case WARNING;
    case ACCENT;
    case DEFAULT;
    case DANGER;
    case PRIMARY;
    case SECONDARY;
    case ERROR;

    // New outline color cases
    case OUTLINE_INFO;
    case OUTLINE_SUCCESS;
    case OUTLINE_WARNING;
    case OUTLINE_ACCENT;
    case OUTLINE_DEFAULT;
    case OUTLINE_DANGER;
    case OUTLINE_PRIMARY;
    case OUTLINE_SECONDARY;
    case OUTLINE_ERROR;

    /**
     * This method returns the name of the given color.
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            Color::INFO => 'info',
            Color::SUCCESS => 'success',
            Color::WARNING => 'warning',
            Color::ACCENT, Color::DEFAULT => 'accent',
            Color::DANGER, Color::ERROR => 'error',
            Color::PRIMARY => 'primary',
            Color::SECONDARY => 'secondary',
            Color::OUTLINE_INFO => 'outline-info',
            Color::OUTLINE_SUCCESS => 'outline-success',
            Color::OUTLINE_WARNING => 'outline-warning',
            Color::OUTLINE_ACCENT, Color::OUTLINE_DEFAULT => 'outline-accent',
            Color::OUTLINE_DANGER, Color::OUTLINE_ERROR => 'outline-error',
            Color::OUTLINE_PRIMARY => 'outline-primary',
            Color::OUTLINE_SECONDARY => 'outline-secondary',
        };
    }

    /**
     * This method returns the color based on the given name.
     * It is used to maintain backwards compatibility to 13.0.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return \Closure|self
     */
    public static function __callStatic($name, $arguments)
    {
        return collect(Color::cases())
            ->filter(fn (Color $color) => $color->name() === $name)
            ->first() ?? Color::ACCENT;
    }
}
