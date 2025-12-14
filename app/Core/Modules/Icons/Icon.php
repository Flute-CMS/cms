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

        $dom = new DOMDocument();
        $dom->loadXML($this->html);

        /** @var DOMElement $item */
        $item = Arr::first($dom->getElementsByTagName('svg'));

        collect($attributes)
            ->each(static fn (string $value, string $key) => $item->setAttribute($key, $value));

        $this->html = $dom->saveHTML();

        return $this;
    }

    /**
     */
    public function __toString(): string
    {
        return $this->toHtml() ?? '';
    }
}
