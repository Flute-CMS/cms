<?php

namespace Flute\Core\Router\Concerns;

use Clickfwd\Yoyo\Exceptions\HttpException;
use Flute\Core\Cache\SWRQueue;
use Flute\Core\Events\OnRouteFoundEvent;
use Flute\Core\Events\RoutingFinishedEvent;
use Flute\Core\Exceptions\ForcedRedirectException;
use Flute\Core\Router\Contracts\RouteInterface;
use Flute\Core\Router\MiddlewareRunner;
use Flute\Core\Router\Route;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Template\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Throwable;

trait HandlesRouteDispatch
{
    public function dispatch(FluteRequest $request): Response
    {
        $this->container->get(Template::class);

        $context = new RequestContext();
        $context->fromRequest($request);

        $isAdmin = is_admin_path();
        $compilable = new RouteCollection();
        $dynamicCollection = new RouteCollection();
        if ($isAdmin) {
            $compilable->addCollection($this->frontCompilableRoutes);
            $compilable->addCollection($this->adminCompilableRoutes);
            $dynamicCollection->addCollection($this->frontDynamicRoutes);
            $dynamicCollection->addCollection($this->adminDynamicRoutes);
        } else {
            $compilable->addCollection($this->frontCompilableRoutes);
            $dynamicCollection->addCollection($this->frontDynamicRoutes);
        }

        $urlMatcher = null;
        $usedCompiledMatcher = false;

        $cacheFile = path('storage/app/cache/routes_compiled' . ( $isAdmin ? '_admin' : '_front' ) . '.php');
        $staleCacheDir = (string) ( config('cache.stale_directory') ?? '' );
        $staleCacheFile = '';
        if ($staleCacheDir !== '') {
            $staleCacheFile =
                rtrim(str_replace('\\', '/', $staleCacheDir), '/')
                . '/routes_compiled'
                . ( $isAdmin ? '_admin' : '_front' )
                . '.php';
        }

        if (!is_debug()) {
            $sourceFile = null;
            if (file_exists($cacheFile)) {
                $sourceFile = $cacheFile;
            } elseif ($staleCacheFile !== '' && file_exists($staleCacheFile)) {
                $sourceFile = $staleCacheFile;
            }

            if ($sourceFile) {
                $compiledRoutes = require $sourceFile;
                if ($compiledRoutes instanceof CompiledUrlMatcher) {
                    $urlMatcher = $compiledRoutes;
                    $urlMatcher->setContext($context);
                    $usedCompiledMatcher = true;
                }
            }
        }

        if (!$urlMatcher) {
            $urlMatcher = new UrlMatcher($compilable, $context);
        }

        if (!is_debug() && !file_exists($cacheFile)) {
            SWRQueue::queue('router.routes_compiled.' . ( $isAdmin ? 'admin' : 'front' ), static function () use (
                $cacheFile,
                $staleCacheFile,
                $compilable,
            ): void {
                if (file_exists($cacheFile)) {
                    return;
                }

                if ($staleCacheFile !== '' && file_exists($staleCacheFile)) {
                    $content = @file_get_contents($staleCacheFile);
                    if (is_string($content) && $content !== '') {
                        @mkdir(dirname($cacheFile), 0o755, true);
                        $tmp = $cacheFile . '.' . uniqid('routes', true) . '.tmp';
                        if (@file_put_contents($tmp, $content, LOCK_EX) !== false) {
                            @rename($tmp, $cacheFile);

                            return;
                        }
                    }
                }

                $lockFile = $cacheFile . '.lock';

                $lockHandle = \Flute\Core\Services\FileLockService::acquireLock($lockFile);
                if ($lockHandle === false) {
                    return;
                }

                try {
                    if (file_exists($cacheFile)) {
                        return;
                    }

                    $dumper = new CompiledUrlMatcherDumper($compilable);
                    $compiledSource = $dumper->dump(['class' => 'FluteCompiledRoutes']);
                    $compiledSource = (string) $compiledSource;
                    $php =
                        ( str_contains($compiledSource, '<?php') ? $compiledSource : "<?php\n" . $compiledSource )
                        . "\nreturn new FluteCompiledRoutes([]);";

                    $tmp = $cacheFile . '.' . uniqid('routes', true) . '.tmp';
                    if (@file_put_contents($tmp, $php, LOCK_EX) !== false) {
                        @rename($tmp, $cacheFile);
                    }
                } catch (Throwable $e) {
                    if (function_exists('logs')) {
                        logs()->warning($e);
                    }
                } finally {
                    \Flute\Core\Services\FileLockService::releaseLock($lockHandle);
                }
            });
        }

        $t0 = microtime(true);

        $runMatched = function (array $parameters) use ($request, $t0): Response {
            \Flute\Core\Router\RoutingTiming::add('Route Matching', microtime(true) - $t0);

            $this->container->get(FluteRequest::class)->attributes->add($parameters);

            $this->currentRoute = $this->resolveRoute($parameters);

            $onRouteEvent = events()->dispatch(
                new OnRouteFoundEvent($request, $this->currentRoute),
                OnRouteFoundEvent::NAME,
            );

            if ($onRouteEvent->isPropagationStopped()) {
                throw new HttpException($onRouteEvent->getErrorCode(), $onRouteEvent->getErrorMessage());
            }

            $middleware = $this->gatherMiddleware();
            $pipeline = new MiddlewareRunner($middleware, $request, fn($request) => $this->runRoute(
                $request,
                $parameters,
            ));

            $tPipe = microtime(true);
            $response = $pipeline->run();
            \Flute\Core\Router\RoutingTiming::add('Middleware+Controller', microtime(true) - $tPipe);

            return $response;
        };

        try {
            $parameters = $urlMatcher->match($request->getPathInfo());
            $response = $runMatched($parameters);
        } catch (ResourceNotFoundException|MethodNotAllowedException $e) {
            $handled = false;

            if ($usedCompiledMatcher) {
                try {
                    $fallbackMatcher = new UrlMatcher($compilable, $context);
                    $parameters = $fallbackMatcher->match($request->getPathInfo());

                    SWRQueue::queue(
                        'router.routes_compiled.rebuild.' . ( $isAdmin ? 'admin' : 'front' ),
                        static function () use ($cacheFile, $compilable): void {
                            $lockFile = $cacheFile . '.lock';
                            $lockHandle = @fopen($lockFile, 'w+');
                            if (!$lockHandle) {
                                return;
                            }

                            if (!@flock($lockHandle, LOCK_EX | LOCK_NB)) {
                                @fclose($lockHandle);

                                return;
                            }

                            try {
                                $dumper = new CompiledUrlMatcherDumper($compilable);
                                $compiledSource = $dumper->dump(['class' => 'FluteCompiledRoutes']);
                                $compiledSource = (string) $compiledSource;
                                $php =
                                    (
                                        str_contains($compiledSource, '<?php')
                                            ? $compiledSource
                                            : "<?php\n" . $compiledSource
                                    ) . "\nreturn new FluteCompiledRoutes([]);";

                                @mkdir(dirname($cacheFile), 0o755, true);
                                $tmp = $cacheFile . '.' . uniqid('routes', true) . '.tmp';
                                if (@file_put_contents($tmp, $php, LOCK_EX) !== false) {
                                    @rename($tmp, $cacheFile);
                                }
                            } catch (Throwable $e) {
                                if (function_exists('logs')) {
                                    logs()->warning($e);
                                }
                            } finally {
                                @flock($lockHandle, LOCK_UN);
                                @fclose($lockHandle);
                                @unlink($lockFile);
                            }
                        },
                    );

                    $response = $runMatched($parameters);
                    $handled = true;
                } catch (ResourceNotFoundException|MethodNotAllowedException) {
                }
            }

            if (!$handled) {
                $dynamicMatcher = new UrlMatcher($dynamicCollection, $context);

                try {
                    $parameters = $dynamicMatcher->match($request->getPathInfo());
                } catch (ResourceNotFoundException|MethodNotAllowedException $dynamicE) {
                    $response = response()->error(404, __('def.page_not_found'));
                    $response->setStatusCode(404);
                }

                if (!isset($response)) {
                    $response = $runMatched($parameters);
                }
            }
        } catch (ForcedRedirectException $exception) {
            $response = response()->redirect($exception->getUrl(), $exception->getStatusCode());
        } catch (HttpException $exception) {
            $response = response()->error($exception->getStatusCode(), $exception->getMessage());
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $response = response()->error($exception->getStatusCode(), $exception->getMessage());
        } catch (\Throwable $exception) {
            if (is_debug()) {
                throw $exception;
            }

            \Flute\Core\Support\ExceptionReporter::report($exception, 'router');

            $response = response()->error(500, __('def.internal_server_error'));
        }

        $event = new RoutingFinishedEvent($response);

        try {
            $event = events()->dispatch($event, RoutingFinishedEvent::NAME);
        } catch (\Throwable $e) {
            if (is_debug()) {
                throw $e;
            }

            if (function_exists('logs')) {
                logs()->error('RoutingFinishedEvent listener failed: ' . $e->getMessage(), ['exception' => $e]);
            }
        }

        if ($event->isPropagationStopped()) {
            throw new HttpException($event->getResponse()->getStatusCode(), $event->getResponse()->getContent());
        }

        return $response;
    }

    /**
     * Ensures compiled routes cache file exists (front/admin).
     */
    public function warmupCompiledRoutes(bool $admin = false): void
    {
        if (is_debug()) {
            return;
        }

        $compilable = new RouteCollection();
        if ($admin) {
            $compilable->addCollection($this->frontCompilableRoutes);
            $compilable->addCollection($this->adminCompilableRoutes);
        } else {
            $compilable->addCollection($this->frontCompilableRoutes);
        }

        $cacheFile = path('storage/app/cache/routes_compiled' . ( $admin ? '_admin' : '_front' ) . '.php');
        if (file_exists($cacheFile)) {
            return;
        }

        $lockFile = $cacheFile . '.lock';
        $lockHandle = @fopen($lockFile, 'w+');
        if (!$lockHandle) {
            return;
        }

        if (!@flock($lockHandle, LOCK_EX | LOCK_NB)) {
            @fclose($lockHandle);

            return;
        }

        try {
            if (file_exists($cacheFile)) {
                return;
            }

            $dumper = new CompiledUrlMatcherDumper($compilable);
            $compiledSource = $dumper->dump(['class' => 'FluteCompiledRoutes']);
            $compiledSource = (string) $compiledSource;
            $php =
                ( str_contains($compiledSource, '<?php') ? $compiledSource : "<?php\n" . $compiledSource )
                . "\nreturn new FluteCompiledRoutes([]);";

            @mkdir(dirname($cacheFile), 0o755, true);
            $tmp = $cacheFile . '.' . uniqid('routes', true) . '.tmp';
            if (@file_put_contents($tmp, $php, LOCK_EX) !== false) {
                @rename($tmp, $cacheFile);
            }
        } catch (Throwable $e) {
            logs()->warning($e);
        } finally {
            @flock($lockHandle, LOCK_UN);
            @fclose($lockHandle);
            @unlink($lockFile);
        }
    }

    protected function resolveRoute(array $parameters): RouteInterface
    {
        $action = $parameters['_controller'] ?? null;
        $routeName = $parameters['_route'] ?? null;
        $middleware = $parameters['_middleware'] ?? [];
        unset($parameters['_controller'], $parameters['_route'], $parameters['_middleware']);

        $symfonyRoute = $this->routes->get($routeName);

        $route = new Route([], '', $action);
        $route->setSymfonyRoute($symfonyRoute);
        $route->setParameters($parameters);
        $route->name($routeName);

        if (!empty($middleware)) {
            $route->middleware($middleware);
        }

        return $route;
    }

    protected function runRoute(FluteRequest $request, array $parameters): Response
    {
        $start = microtime(true);
        $response = $this->currentRoute->run($request, $parameters, $this->container);
        \Flute\Core\Router\RoutingTiming::add('Controller', microtime(true) - $start);

        return $response;
    }

    protected function buildRoutePathIndex(): void
    {
        $this->routePathIndex = [];

        foreach ($this->routes->all() as $route) {
            $path = $route->getPath();
            $this->routePathIndex[$path][] = $route->getMethods();
        }
    }
}
