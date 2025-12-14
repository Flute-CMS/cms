<?php

namespace Flute\Core\Modules\Installer\Components;

use Flute\Core\Support\FluteComponent;

class WelcomeComponent extends FluteComponent
{
    /**
     * Render the welcome component
     *
     * @return string
     */
    public function render()
    {
        return view('installer::yoyo.welcome');
    }
}
