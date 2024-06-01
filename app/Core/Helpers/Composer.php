<?php

use Flute\Core\Composer\ComposerManager;

if (!function_exists("composer")) 
{
    /**
     * Get the composer manager instance
     * 
     * @return ComposerManager
     */
    function composer()
    {
        return new ComposerManager;
    }
}
