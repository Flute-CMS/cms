<?php

namespace Flute\Core\Router;

use Cycle\ORM\Exception\SchemaException;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver as SymfonyControllerResolver;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use ReflectionException;

class ContainerControllerResolver
{
    protected Container $container;
    protected SymfonyControllerResolver $resolver;

    /**
     * Class constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->resolver = new SymfonyControllerResolver($container);
    }

    /**
     * Retrieves the controller for handling the request.
     *
     * @param  FluteRequest  $request
     * @return callable|array|null
     */
    public function getController(FluteRequest $request)
    {
        return $this->resolver->getController($request);
    }

    /**
     * Retrieves the arguments for invoking the controller.
     *
     * @param FluteRequest $request
     * @param mixed $controller
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function getArguments(FluteRequest $request, $controller): array
    {
        if (gettype($controller) === 'object') {
            return [];
        }

        $reflectionMethod = new \ReflectionMethod($controller[0], $controller[1]);
        $params = $reflectionMethod->getParameters();
        $args = [];

        foreach ($params as $param) {
            if ($param->getType() && !$param->getType()->isBuiltin()) {
                $paramClass = $param->getType()->getName();

                if (!is_installed()) {
                    $args[] = $this->container->get($paramClass);
                    continue;
                }
                
                try {
                    $id = $request->attributes->get($param->getName());
                    $args[] = $this->fetchModel($paramClass, $id);
                } catch (SchemaException $e) {
                    $args[] = $this->container->get($paramClass);
                }
            } else if ($request->attributes->has($param->name)) {
                $args[] = $request->attributes->get($param->name);
            }
        }

        return $args;
    }

    private function fetchModel(string $className, $id): object
    {
        $key = str_replace(['/', '\\'], ['_', '_'], "model_binding_$className");
        $has = cache()->has($key);

        if ($has && cache($key) !== true)
            throw new SchemaException;

        try {
            $rep = rep($className);
            !$has && cache()->set($key, true);
        } catch (SchemaException $e) {
            !$has && cache()->set($key, false);
            throw $e;
        }
        if ((int) $id === 0)
            throw new ResourceNotFoundException();

        $result = $rep->findByPK($id);

        if (!$result)
            throw new ResourceNotFoundException();

        return $result;
    }
}