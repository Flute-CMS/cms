<?php

namespace Flute\Core\Support\Htmx;

use Symfony\Component\HttpFoundation\HeaderBag;

class HtmxRequest
{
    public const HX_BOOSTED = 'HX-Boosted';
    public const HX_CURRENT_URL = 'HX-Current-URL';
    public const HX_HISTORY_RESTORE_REQUEST = 'HX-History-Restore-Request';
    public const HX_PROMPT = 'HX-Prompt';
    public const HX_REQUEST = 'HX-Request';
    public const HX_TARGET = 'HX-Target';
    public const HX_TRIGGER = 'HX-Trigger';
    public const HX_TRIGGER_NAME = 'HX-Trigger-Name';
    protected HeaderBag $headers;

    public function __construct(HeaderBag $headers)
    {
        $this->headers = $headers;
    }

    /**
     * The current URL of the browser when the htmx request was made.
     *
     * @return string|null
     */
    public function getCurrentUrl(): ?string
    {
        return $this->headers->get(self::HX_CURRENT_URL);
    }

    /**
     * The user response to an hx-prompt.
     *
     * @return string|null
     */
    public function getPromptResponse(): ?string
    {
        return $this->headers->get(self::HX_PROMPT);
    }

    /**
     * The id of the target element if it exists.
     *
     * @return string|null
     */
    public function getTarget(): ?string
    {
        return $this->headers->get(self::HX_TARGET);
    }

    /**
     * The id of the triggered element if it exists.
     *
     * @return string|null
     */
    public function getTriggerId(): ?string
    {
        return $this->headers->get(self::HX_TRIGGER);
    }

    /**
     * The name of the triggered element if it exists.
     *
     * @return string|null
     */
    public function getTriggerName(): ?string
    {
        return $this->headers->get(self::HX_TRIGGER_NAME);
    }

    /**
     * Whether the request is via an element using hx-boost.
     *
     * @return bool
     */
    public function isBoosted(): bool
    {
        return filter_var($this->headers->get(self::HX_BOOSTED, 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Whether the request is for history restoration after a miss in the local history cache
     *
     * @return bool
     */
    public function isHistoryRestoreRequest(): bool
    {
        return filter_var($this->headers->get(self::HX_HISTORY_RESTORE_REQUEST, 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Whether the request is made via htmx.
     *
     * @return bool
     */
    public function isHtmxRequest(): bool
    {
        return filter_var($this->headers->get(self::HX_REQUEST, 'false'), FILTER_VALIDATE_BOOLEAN);
    }
}
