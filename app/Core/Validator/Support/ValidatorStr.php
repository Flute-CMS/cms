<?php

namespace Flute\Core\Validator\Support;

class ValidatorStr
{
    /**
     * Prettify the attribute name.
     *
     * @param string $atr
     *
     * @return string
     */
    public static function prettyAttribute($atr)
    {
        return ucfirst(str_replace(['.*', '.', '_'], ['', ' ', ' '], $atr));
    }

    /**
     * Does `$a` overlap with `$b` from the left-hand side.
     *
     * @return bool|string
     */
    public static function overlapLeft(string $a, string $b)
    {
        if (empty($b)) {
            return false;
        }

        if ($a === $b) {
            return $b;
        }

        if (substr_count($a, '.') > substr_count($b, '.')) {
            return static::overlapLeft(substr($a, 0, strrpos($a, '.')), $b);
        }

        return static::overlapLeft($a, substr($b, 0, strrpos($b, '.')));

    }

    /**
     * Merge the overlap of pattern, field, and attribute.
     *
     * @param string $overlap    Str::overlapLeft of a pattern (foo.*.bar) and field (foo.*.bax)
     * @param string $attribute  Realised attribute name (foo.0.bar)
     * @param string $field      Field name (foo.*.bax)
     *
     * @return bool|string
     */
    public static function overlapLeftMerge($overlap, $attribute, $field)
    {
        $overlap = explode('.', $overlap);
        $attribute = explode('.', $attribute);
        $field = explode('.', $field);

        for ($i = 0; $i < count($overlap); $i++) {
            $field[$i] = $attribute[$i];
        }

        return implode('.', $field);
    }
}
