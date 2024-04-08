<?php

namespace Flute\Core\Http\Controllers;

use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class NotificationController extends AbstractController
{
    public function getAll() 
    {
        return $this->json([
            "result" => notification()->all(true)
        ]);
    }

    public function getUnread() 
    {
        return $this->json([
            "result" => notification()->unread(true)
        ]);
    }

    public function delete( FluteRequest $fluteRequest, $id )
    {
        notification()->delete((int) $id);

        return $this->success();
    }

    public function read( FluteRequest $fluteRequest, $id )
    {
        notification()->setViewed((int) $id);

        return $this->success();
    }

    public function clear( FluteRequest $fluteRequest )
    {
        notification()->clear();

        return $this->success();
    }
}