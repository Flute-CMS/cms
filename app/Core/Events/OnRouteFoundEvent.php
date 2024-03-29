<?php

namespace Flute\Core\Events;

use Flute\Core\Support\FluteRequest;
use Symfony\Contracts\EventDispatcher\Event;

class OnRouteFoundEvent extends Event
{
    public const NAME = 'routing.found';

    private FluteRequest $request;
    protected string $route;
    protected $controller;
    protected $errorCode = 404;
    protected $errorMessage = 'Not Found';

    public function __construct(FluteRequest $request, $route, $controller)
    {
        $this->route = $route;
        $this->controller = $controller;
        $this->request = $request;
    }
    
    public function getRoute(): string
    {
        return $this->route;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function getRequest(): FluteRequest
    {
        return $this->request;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function setErrorCode(int $errorCode): self
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function setErrorMessage(string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        
        return $this;
    }
}
