<?php

namespace Flute\Core\Modules\Profile\Controllers\Htmx;

use Flute\Core\Support\BaseController;

class ProfileSidebar extends BaseController
{
    public function open()
    {
        return $this->htmxRender('flute::partials.miniprofile', [
            'user' => user()->getCurrentUser(),
        ])->setTriggers('open-right-sidebar');
    }
}
