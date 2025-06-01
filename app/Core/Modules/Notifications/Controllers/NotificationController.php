<?php

namespace Flute\Core\Modules\Notifications\Controllers;

use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class NotificationController extends BaseController
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

    public function getCountUnread()
    {
        return response()->make(notification()->countUnread());
    }

    public function delete(FluteRequest $fluteRequest, $id)
    {
        notification()->delete((int) $id);

        return $this->success();
    }

    public function read(FluteRequest $fluteRequest, $id)
    {
        notification()->setViewed((int) $id);

        return $this->success();
    }

    public function clear(FluteRequest $fluteRequest)
    {
        notification()->clear();

        return $this->success();
    }
}