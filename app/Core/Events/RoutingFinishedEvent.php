<?php

namespace Flute\Core\Events;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;

class RoutingFinishedEvent extends Event
{
    public const NAME = 'routing.finished';

    private $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}
