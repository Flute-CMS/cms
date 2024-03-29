<?php

use Flute\Core\Template\Template;

if (!function_exists("template")) {
    function template() : Template
    {
        return app(Template::class);
    }
}
