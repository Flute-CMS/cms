<?php

namespace Flute\Core\Events;

use Flute\Core\Router\Contracts\RouteInterface;
use Flute\Core\Support\FluteRequest;
use Symfony\Contracts\EventDispatcher\Event;

class OnRouteFoundEvent extends Event
{
    public const NAME = 'routing.found';

    protected ?RouteInterface $route;

    protected $errorCode = 404;

    protected $errorMessage = 'Not Found';

    private FluteRequest $request;

    public function __construct(FluteRequest $request, ?RouteInterface $route)
    {
        $this->route = $route;
        $this->request = $request;
    }

    public function getRoute(): RouteInterface|null
    {
        return $this->route;
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
