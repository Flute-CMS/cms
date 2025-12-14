<?php

use Flute\Core\Modules\Payments\Initializers\GatewayInitializer;

if( !function_exists("payments") )
{
    function payments() : GatewayInitializer
    {
        return app(GatewayInitializer::class);
    }
}
