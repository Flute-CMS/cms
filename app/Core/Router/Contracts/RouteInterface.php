<?php

namespace Flute\Core\Router\Contracts;

use DI\Container;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

interface RouteInterface
{
    public function run(FluteRequest $request, array $parameters, Container $container): Response;

    public function middleware(array|string|null $middleware): self;

    public function withoutMiddleware(array|string $middleware): self;

    public function name(string $name): self;

    public function where(string|array $parameter, string|null $pattern = null): self;

    public function defaults(string $key, mixed $value): self;

    public function getName(): ?string;

    public function getUri(): string;

    public function setParameters(array $parameters): void;

    public function getMiddleware(): array;

    public function getExcludedMiddleware(): array;

    public function getRequirements(): array;

    public function getDefaults(): array;

    public function getAction(): mixed;
}
