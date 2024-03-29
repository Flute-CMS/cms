<?php

namespace Flute\Core\Installer;

class InstallerView
{
    public function stepAll() : int
    {
        return (int) config('installer.step');
    }

    public function stepCurrent() : int
    {
        $requestUri = request()->getRequestUri();
        
        return (int) substr($requestUri, strrpos("/$requestUri", '/'));
    }
}