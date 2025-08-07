<?php

namespace Flute\Core\Router;

use Cycle\ORM\Exception\SchemaException;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Support\FluteRequest;
use ReflectionException;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver as SController;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class ContainerControllerResolver
{
    protected Container $container;
    protected SController $resolver;

    /**
     * Class constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->resolver = new SController($container);
    }

    /**
     * Retrieves the controller for handling the request.
     *
     * @param FluteRequest $request
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
        $reflectionMethod = new \ReflectionMethod($controller[0], $controller[1]);
        $params = $reflectionMethod->getParameters();
        $args = [];

        foreach ($params as $param) {
            if ($param->getType() && !$param->getType()->isBuiltin()) {
                $paramClass = $param->getType()->getName();

                try {
                    $id = $request->attributes->get($param->getName());
                    $args[] = $this->fetchModel($paramClass, $id);
                } catch (SchemaException | ResourceNotFoundException $e) {
                    $args[] = $this->container->get($paramClass);
                }
            } elseif ($request->attributes->has($param->getName())) {
                $args[] = $request->attributes->get($param->getName());
            } elseif ($param->isOptional()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException("Missing argument: " . $param->getName());
            }
        }

        return $args;
    }

    /**
     * Fetch model based on class name and ID.
     *
     * @param string $className
     * @param mixed $id
     * @return object
     * @throws ResourceNotFoundException
     */
    private function fetchModel(string $className, $id): object
    {
        // Кэширование моделей для оптимизации
        $key = str_replace(['/', '\\'], ['_', '_'], "model_binding_$className");
        $has = cache()->has($key);

        if ($has && cache($key) !== true) {
            throw new SchemaException("Model not found in cache.");
        }

        try {
            $rep = rep($className);
            !$has && cache()->set($key, true);
        } catch (SchemaException $e) {
            !$has && cache()->set($key, false);

            throw $e;
        }

        if ((int) $id === 0) {
            throw new ResourceNotFoundException("Invalid ID: $id");
        }

        $result = $rep->findByPK($id);

        if (!$result) {
            throw new ResourceNotFoundException("Model not found for ID: $id");
        }

        return $result;
    }
}
