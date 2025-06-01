<?php

use Flute\Core\App;
use Flute\Core\Services\ThrottlerService;

/**
 * Почему Stuff и че это за?
 * 
 * Дело в том, что у меня дикая лень писать что-то много раз,
 * и поэтому я решил сделать функцию хелпер, которая будет хранить
 * разные полезные штуки, типо добавление роли, разрешений, может SourceQuery.
 * 
 * Короче класс включающий stuff. На то и название. Делить на каждое добавление по классу
 * мне не охота.
 */

if( !function_exists( "throttler" ) )
{
    function throttler() : ThrottlerService
    {
        return app( ThrottlerService::class );
    }
}