<?php

namespace Flute\Core\Support\Htmx\Response;

class HtmxClientRefreshResponse extends HtmxResponse
{
    public const HX_REFRESH = 'HX-Refresh';

    public function __construct()
    {
        parent::__construct('', self::HTTP_OK, [self::HX_REFRESH => 'true']);
    }
}
