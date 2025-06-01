<?php

use Flute\Core\Services\FileSystemService;
use Symfony\Component\Finder\Finder;

if (!function_exists("fs")) {
    /**
     * Get the files instance
     * 
     * @return FileSystemService
     */
    function fs() : FileSystemService
    {
        return app(FileSystemService::class);
    }
}

if (!function_exists("finder")) {
    /**
     * Get the finder instance
     * 
     * @return Finder
     */
    function finder() : Finder
    {
        return app()->make(Finder::class);
    }
}