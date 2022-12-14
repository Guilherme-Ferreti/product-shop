<?php

declare(strict_types=1);

namespace App\Http\Routes;

use App\Exceptions\MethodNotAllowedException;
use App\Exceptions\RouteNotFoundException;
use App\Http\Middlewares\OverrideHttpMethodMiddleware;
use App\Http\Middlewares\SaveLastRouteMiddleware;
use App\Http\Middlewares\SessionMiddleware;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

class Router
{
    protected array $middlewares = [
        SessionMiddleware::class,
        OverrideHttpMethodMiddleware::class,
        SaveLastRouteMiddleware::class,
    ];

    public function resolve()
    {
        $this->runMiddlewares($this->middlewares);

        $requestUri = $_SERVER['REQUEST_URI'];
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        $requestUri = rawurldecode(explode('?', $requestUri)[0]);

        $result = $this->registerRoutes()->dispatch($requestMethod, $requestUri);

        return $this->handleRouteResult($result);
    }

    private function runMiddlewares(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            call_user_func(new $middleware());
        }
    }

    private function registerRoutes(): Dispatcher
    {
        return \FastRoute\simpleDispatcher(function (RouteCollector $router) {
            require_once config('routes.path');
        });
    }

    private function handleRouteResult(array $routeInfo)
    {
        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowedException();
        }

        if ($routeInfo[0] === Dispatcher::FOUND) {
            [$class, $method, $middlewares, $pathParams] = $this->parseRouteInfo($routeInfo);

            if (class_exists($class)) {
                $class = new $class();

                if (method_exists($class, $method)) {
                    $this->runMiddlewares($middlewares);

                    return call_user_func_array([$class, $method], $pathParams);
                }
            }
        }

        throw new RouteNotFoundException();
    }

    private function parseRouteInfo(array $routeInfo): array
    {
        $routeDefinition = $routeInfo[1];
        $pathParams = $routeInfo[2];

        if (isset($routeDefinition['controller'])) {
            $class = $routeDefinition['controller'][0] ?? '';
            $method = $routeDefinition['controller'][1] ?? '';
            $middlewares = $routeDefinition['middlewares'] ?? [];
        } else {
            $class = $routeDefinition[0] ?? '';
            $method = $routeDefinition[1] ?? '';
            $middlewares = [];
        }

        return [$class, $method, $middlewares, $pathParams];
    }
}
