<?php

namespace Flute\Core\Events;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

class ResponseEvent extends Event
{
    public const NAME = 'flute.response';

    private Response $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
