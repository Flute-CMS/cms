<?php

namespace Flute\Core\Modules\Tips\Controllers;

use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class TipController extends BaseController
{
    public function complete(FluteRequest $request)
    {
        $tip = (string) $request->input('tip');

        if (!$tip || !config("tips_complete.{$tip}") || $tip && config("tips_complete.{$tip}.completed") == true) {
            return $this->error('Tip key is required or is already complete');
        }

        if (!user()->can(config("tips_complete.{$tip}.permission"))) {
            return $this->error('You do not have permission to complete this tip');
        }

        $tips = config('tips_complete');
        $tips[$tip]['completed'] = true;

        fs()->updateConfig(BASE_PATH . 'config/tips_complete.php', $tips);

        return $this->success('Tip completed successfully');
    }
}
