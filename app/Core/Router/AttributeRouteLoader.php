<?php

namespace Flute\Core\Router;

use Flute\Core\Router\Annotations\Middleware as MiddlewareAttribute;
use Flute\Core\Router\Annotations\Route as RouteAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionAttribute;
use Symfony\Component\Finder\Finder;

class AttributeRouteLoader
{
    /**
     * @var Router The router instance
     */
    private Router $router;

    /**
     * @var array Cache of registered controller classes
     */
    private array $registeredControllers = [];
    
    /**
     * @var array Cache of controller middleware
     */
    private array $middlewareCache = [];
    
    /**
     * @var array Cache of class route chains
     */
    private array $classRouteCache = [];
    
    /**
     * @var array Cache of loaded class names by directory
     */
    private array $loadedClassNamesCache = [];

    /**
     * Constructor
     * 
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Load routes from all controllers in the given directories
     *
     * @param array $directories Directories to scan for controllers
     * @param string $namespace Base namespace for the controllers
     * @return int Number of routes registered
     */
    public function loadFromDirectories(array $directories, string $namespace): int
    {
        $routeCount = 0;
        $cacheKey = 'route_loader_' . md5(implode('|', $directories) . '_' . $namespace);
        $cachedData = !is_debug() ? cache()->get($cacheKey) : null;
        
        if ($cachedData !== null) {
            $classNames = $cachedData;
        } else {
            $classNames = $this->scanDirectoriesForControllers($directories, $namespace);
            
            if (!empty($classNames) && !is_debug()) {
                cache()->set($cacheKey, $classNames, 86400); // Cache for 1 day
            }
        }
        
        foreach ($classNames as $className) {
            $routeCount += $this->loadFromClass($className);
        }

        return $routeCount;
    }
    
    /**
     * Scan directories for controller classes
     * 
     * @param array $directories
     * @param string $namespace
     * @return array
     */
    private function scanDirectoriesForControllers(array $directories, string $namespace): array
    {
        $cacheKey = 'route_loader_dirs_' . md5(implode('|', $directories));
        
        if (isset($this->loadedClassNamesCache[$cacheKey])) {
            return $this->loadedClassNamesCache[$cacheKey];
        }
        
        $classNames = [];
        $finder = new Finder();
        $finder->files()->name('*.php')->in($directories);

        foreach ($finder as $file) {
            $className = $this->getClassNameFromFile($file, $directories, $namespace);
            if ($className && class_exists($className)) {
                $classNames[] = $className;
            }
        }
        
        $this->loadedClassNamesCache[$cacheKey] = $classNames;
        return $classNames;
    }

    /**
     * Load routes from the given class
     *
     * @param string $className Fully qualified class name
     * @return int Number of routes registered
     */
    public function loadFromClass(string $className): int
    {
        if (isset($this->registeredControllers[$className])) {
            return 0;
        }
        $this->registeredControllers[$className] = true;
        $routeCount = 0;

        try {
            $reflectionClass = new ReflectionClass($className);

            $inheritedClassRoute = $this->buildClassRouteChain($reflectionClass);

            $classMiddleware = $this->getInheritedClassMiddleware($reflectionClass);

            $publicMethods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
            
            foreach ($publicMethods as $method) {
                if ($method->isStatic()) {
                    continue;
                }
                $routeAttributes = $method->getAttributes(
                    RouteAttribute::class,
                    ReflectionAttribute::IS_INSTANCEOF
                );
                if (empty($routeAttributes)) {
                    continue;
                }

                $methodMiddleware = $this->getMethodMiddleware($method);
                $combinedMiddleware = array_merge($classMiddleware, $methodMiddleware);

                foreach ($routeAttributes as $routeAttribute) {
                    $methodRoute = $routeAttribute->newInstance();

                    $finalRoute = $inheritedClassRoute
                        ? \Flute\Core\Router\Annotations\Route::inherit($inheritedClassRoute, $methodRoute)
                        : $methodRoute;

                    $annotationRoute = $finalRoute;
                    
                    $action = [$className, $method->getName()];
                    $routeInstance = $this->router->addRoute(
                        $finalRoute->getMethods(),
                        $finalRoute->getUri(),
                        $action
                    );

                    $annotationRoute->setAfterModifyCallback(function($annotationRoute) use ($routeInstance) {
                        if ($annotationRoute->getName()) {
                            $routeInstance->name($annotationRoute->getName());
                        }
                        
                        if (!empty($annotationRoute->getMiddleware())) {
                            $routeInstance->middleware($annotationRoute->getMiddleware());
                        }
                        
                        foreach ($annotationRoute->getWhere() as $param => $pattern) {
                            $routeInstance->where($param, $pattern);
                        }
                        
                        foreach ($annotationRoute->getDefaults() as $param => $value) {
                            $routeInstance->defaults($param, $value);
                        }
                    });

                    if ($finalRoute->getName()) {
                        $routeInstance->name($finalRoute->getName());
                    }
                    
                    // middleware
                    $allMiddleware = array_merge($combinedMiddleware, $finalRoute->getMiddleware());
                    if (!empty($allMiddleware)) {
                        $routeInstance->middleware($allMiddleware);
                    }
                    
                    // where
                    foreach ($finalRoute->getWhere() as $param => $pattern) {
                        $routeInstance->where($param, $pattern);
                    }
                    
                    // defaults
                    foreach ($finalRoute->getDefaults() as $param => $value) {
                        $routeInstance->defaults($param, $value);
                    }

                    $routeCount++;
                }
            }

            return $routeCount;

        } catch (\ReflectionException $e) {
            return 0;
        }
    }

    private function getInheritedClassMiddleware(ReflectionClass $class): array
    {
        $className = $class->getName();
        
        if (isset($this->middlewareCache[$className])) {
            return $this->middlewareCache[$className];
        }
        
        $middleware = [];
        $parent = $class->getParentClass();
        
        if ($parent) {
            $middleware = $this->getInheritedClassMiddleware($parent);
        }

        $middlewareAttributes = $class->getAttributes(
            MiddlewareAttribute::class,
            ReflectionAttribute::IS_INSTANCEOF
        );
        
        foreach ($middlewareAttributes as $attribute) {
            $middlewareInstance = $attribute->newInstance();
            $middleware = array_merge($middleware, $middlewareInstance->getMiddleware());
        }
        
        $this->middlewareCache[$className] = $middleware;
        
        return $middleware;
    }

    /**
     * Get middleware defined at the method level
     *
     * @param ReflectionMethod $method
     * @return array
     */
    private function getMethodMiddleware(ReflectionMethod $method): array
    {
        $methodKey = $method->getDeclaringClass()->getName() . '::' . $method->getName();
        
        if (isset($this->middlewareCache[$methodKey])) {
            return $this->middlewareCache[$methodKey];
        }
        
        $middleware = [];
        $middlewareAttributes = $method->getAttributes(
            MiddlewareAttribute::class,
            ReflectionAttribute::IS_INSTANCEOF
        );

        foreach ($middlewareAttributes as $attribute) {
            $middlewareInstance = $attribute->newInstance();
            $middleware = array_merge($middleware, $middlewareInstance->getMiddleware());
        }
        
        $this->middlewareCache[$methodKey] = $middleware;
        
        return $middleware;
    }

    /**
     * Get fully qualified class name from file
     *
     * @param \SplFileInfo $file
     * @param array $directories
     * @param string $namespace
     * @return string|null
     */
    private function getClassNameFromFile(\SplFileInfo $file, array $directories, string $namespace): ?string
    {
        $path = $file->getRealPath();
        $cacheKey = 'class_from_file_' . md5($path);
        
        if (isset($this->loadedClassNamesCache[$cacheKey])) {
            return $this->loadedClassNamesCache[$cacheKey];
        }

        $basePath = null;
        foreach ($directories as $directory) {
            $realDirectory = realpath($directory);
            if ($realDirectory && strpos($path, $realDirectory) === 0) {
                $basePath = $realDirectory;
                break;
            }
        }

        if ($basePath === null) {
            $this->loadedClassNamesCache[$cacheKey] = null;
            return null;
        }

        $relativePath = substr($path, strlen($basePath) + 1);
        $relativePathWithoutExtension = substr($relativePath, 0, -4); // Remove .php
        $namespaceSegments = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePathWithoutExtension);

        $result = $namespace . '\\' . $namespaceSegments;
        $this->loadedClassNamesCache[$cacheKey] = $result;
        
        return $result;
    }

    /**
     * Recursively builds the final Route (prefix + name, etc.) for the class,
     * starting with the highest parent and descending to the current class.
     * 
     * @param ReflectionClass $class
     * @return \Flute\Core\Router\Annotations\Route|null
     */
    private function buildClassRouteChain(ReflectionClass $class): ?\Flute\Core\Router\Annotations\Route
    {
        $className = $class->getName();
        
        if (isset($this->classRouteCache[$className])) {
            return $this->classRouteCache[$className];
        }
        
        $parent = $class->getParentClass();
        $parentRoute = null;

        if ($parent !== false && $parent !== null) {
            $parentRoute = $this->buildClassRouteChain($parent);
        }

        $routeAttributes = $class->getAttributes(
            RouteAttribute::class,
            ReflectionAttribute::IS_INSTANCEOF
        );

        $classRoute = null;
        if (!empty($routeAttributes)) {
            $classRoute = $routeAttributes[0]->newInstance();
        }

        $result = null;
        if ($parentRoute && $classRoute) {
            $result = \Flute\Core\Router\Annotations\Route::inherit($parentRoute, $classRoute);
        } elseif ($parentRoute) {
            $result = $parentRoute;
        } else {
            $result = $classRoute;
        }
        
        $this->classRouteCache[$className] = $result;
        
        return $result;
    }
}