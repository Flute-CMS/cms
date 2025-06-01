<?php

namespace Flute\Core\Support\Htmx\Response;

class HtmxClientRedirectResponse extends HtmxResponse
{

    public const HX_REDIRECT = 'HX-Redirect';

    public function __construct(string $url)
    {
        parent::__construct('', self::HTTP_OK, [self::HX_REDIRECT => $url]);
    }
}