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
        static $instance = null;

        if ($instance === null) {
            $instance = new ComposerManager;
        }

        return $instance;
    }
}
