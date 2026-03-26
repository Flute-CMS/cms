<?php

namespace Flute\Core\Modules\Icons;

use DOMDocument;
use DOMElement;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Stringable;

class Icon extends HtmlString implements Stringable
{
    /**
     * In-memory (per-request) cache for rendered icon HTML.
     */
    private static array $renderCache = [];

    /**
     * SVG symbols already emitted on the current page.
     * Key = symbolId, value = true.
     */
    private static array $emittedSymbols = [];

    /**
     * Determine if the given HTML string is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->html);
    }

    /**
     *
     */
    public function setAttributes(iterable $attributes): string
    {
        if ($this->isEmpty()) {
            return $this;
        }

        $attrs = collect($attributes)->filter(static fn($v) => is_string($v))->all();
        $cacheKey = 'icon.' . md5($this->html . serialize($attrs));

        // 1. In-memory cache (fastest, per-request)
        if (isset(self::$renderCache[$cacheKey])) {
            $this->html = self::$renderCache[$cacheKey];

            return $this;
        }

        // 2. Persistent cache (survives between requests)
        try {
            if (function_exists('cache')) {
                $cached = cache()->get($cacheKey);
                if ($cached !== null) {
                    self::$renderCache[$cacheKey] = $cached;
                    $this->html = $cached;

                    return $this;
                }
            }

            // @mago-expect no-empty-catch-clause -- cache unavailable during early boot
        } catch (\Throwable) {
        }

        // 3. Build from scratch
        $dom = new DOMDocument();
        $dom->loadXML($this->html);

        /** @var DOMElement $item */
        $item = Arr::first($dom->getElementsByTagName('svg'));

        foreach ($attrs as $key => $value) {
            $item->setAttribute($key, $value);
        }

        $this->html = $dom->saveHTML();
        self::$renderCache[$cacheKey] = $this->html;

        // Persist for 24h
        try {
            if (function_exists('cache')) {
                cache()->set($cacheKey, $this->html, 86400);
            }

            // @mago-expect no-empty-catch-clause -- cache unavailable during early boot
        } catch (\Throwable) {
        }

        return $this;
    }

    /**
     * Reset per-request symbol tracking (called at start of each page render).
     */
    public static function resetSymbols(): void
    {
        self::$emittedSymbols = [];
    }

    /**
     */
    public function __toString(): string
    {
        return $this->toHtml() ?? '';
    }
}
