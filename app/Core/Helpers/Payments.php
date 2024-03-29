<?php

use Flute\Core\Payments\GatewayInitializer;

if( !function_exists("payments") )
{
    function payments() : GatewayInitializer
    {
        return app(GatewayInitializer::class);
    }
}
