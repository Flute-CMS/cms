<?php

namespace Flute\Core\Support\Htmx\Response;

class HtmxResponse extends \Symfony\Component\HttpFoundation\Response
{

    public const HX_LOCATION = 'HX-Location';
    public const HX_PUSH_URL = 'HX-Push-Url';
    public const HX_REPLACE_URL = 'HX-Replace-Url';
    public const HX_RESWAP = 'HX-Reswap';
    public const HX_RETARGET = 'HX-Retarget';
    public const HX_RESELECT = 'HX-Reselect';
    public const HX_TRIGGER = 'HX-Trigger';
    public const HX_TRIGGER_AFTER_SETTLE = 'HX-Trigger-After-Settle';
    public const HX_TRIGGER_AFTER_SWAP = 'HX-Trigger-After-Swap';

    public function setLocation(string $path, ?array $context = null): static
    {
        if (!empty($context)) {
            $path = json_encode(array_merge(['path' => $path], $context));
        }
        $this->headers->set(self::HX_LOCATION, $path);
        return $this;
    }

    public function setPushUrl(string $url): static
    {
        $this->headers->set(self::HX_PUSH_URL, $url);
        return $this;
    }

    public function setReplaceUrl(string $url): static
    {
        $this->headers->set(self::HX_REPLACE_URL, $url);
        return $this;
    }

    public function setReswap(string $option): static
    {
        $this->headers->set(self::HX_RESWAP, $option);
        return $this;
    }

    public function setRetarget(string $selector): static
    {
        $this->headers->set(self::HX_RETARGET, $selector);
        return $this;
    }

    public function setTriggers(string|array $events): static
    {
        return $this->_setTriggers(self::HX_TRIGGER, $events);
    }

    public function setAfterSettleTriggers(string|array $events): static
    {
        return $this->_setTriggers(self::HX_TRIGGER_AFTER_SETTLE, $events);
    }

    public function setAfterSwapTriggers(string|array $events): static
    {
        return $this->_setTriggers(self::HX_TRIGGER_AFTER_SWAP, $events);
    }

    private function _setTriggers(string $key, string|array $value): static
    {
        if ($value === '' || $value === []) {
            throw new \InvalidArgumentException("Trigger value MUST be an non-empty string or array");
        }
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $this->headers->set($key, $value);
        return $this;
    }
}