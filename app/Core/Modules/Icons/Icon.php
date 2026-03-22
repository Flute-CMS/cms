<?php

namespace Flute\Core\Modules\Icons;

use DOMDocument;
use DOMElement;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Stringable;

class Icon extends HtmlString implements Stringable
{
    private static array $renderCache = [];

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
        $cacheKey = md5($this->html . serialize($attrs));

        if (isset(self::$renderCache[$cacheKey])) {
            $this->html = self::$renderCache[$cacheKey];
            return $this;
        }

        $dom = new DOMDocument();
        $dom->loadXML($this->html);

        /** @var DOMElement $item */
        $item = Arr::first($dom->getElementsByTagName('svg'));

        foreach ($attrs as $key => $value) {
            $item->setAttribute($key, $value);
        }

        $this->html = $dom->saveHTML();
        self::$renderCache[$cacheKey] = $this->html;

        return $this;
    }

    /**
     */
    public function __toString(): string
    {
        return $this->toHtml() ?? '';
    }
}
