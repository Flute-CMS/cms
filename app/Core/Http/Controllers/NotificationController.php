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

    public function delete( FluteRequest $fluteRequest, int $id )
    {
        notification()->delete($id);

        return $this->success();
    }

    public function read( FluteRequest $fluteRequest, int $id )
    {
        notification()->setViewed($id);

        return $this->success();
    }

    public function clear( FluteRequest $fluteRequest )
    {
        notification()->clear();

        return $this->success();
    }
}