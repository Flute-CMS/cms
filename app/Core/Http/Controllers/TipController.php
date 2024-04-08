<?php

namespace Flute\Core\Http\Controllers;

use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class TipController extends AbstractController
{
    public function complete( FluteRequest $request ) 
    {
        // abort_if(!user()->hasPermission('admin.panel'), 403);

        $tip = (string) $request->input('tip');

        if( !$tip || !config("tips_complete.$tip") || $tip && config("tips_complete.$tip.completed") == true )
            return $this->error('Tip key is required or is already complete');

        if( !user()->hasPermission( config("tips_complete.$tip.permission") ) )
            return $this->error('You do not have permission to complete this tip');

        $tips = config('tips_complete');
        $tips[$tip]['completed'] = true;

        fs()->updateConfig(BASE_PATH . 'config/tips_complete.php', $tips);
        
        user()->log('events.tip_completed', $tip);

        return $this->success();
    }
}