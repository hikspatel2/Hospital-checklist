<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Safe MVC Router supporting parameterized routes, HTTP verbs, and middleware
 */
class Router
{
    private array $routes = [];
    private array $namedRoutes = [];

    public function get(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): self
    {
        // Automatically attach CSRF verification for POST unless explicitly bypassed
        if (!in_array('csrf', $middlewares, true) && !in_array('no-csrf', $middlewares, true)) {
            $middlewares[] = 'csrf';
        }
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, array|callable $handler, array $middlewares = []): self
    {
        if (!in_array('csrf', $middlewares, true) && !in_array('no-csrf', $middlewares, true)) {
            $middlewares[] = 'csrf';
        }
        return $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, array|callable $handler, array $middlewares = []): self
    {
        if (!in_array('csrf', $middlewares, true) && !in_array('no-csrf', $middlewares, true)) {
            $middlewares[] = 'csrf';
        }
        return $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    public function addRoute(string $method, string $path, array|callable $handler, array $middlewares = []): self
    {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $normalizedPath,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'regex' => $this->compilePattern($normalizedPath),
        ];

        return $this;
    }

    private function compilePattern(string $path): string
    {
        // Convert route params {param} or {param:id} to regex named groups
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(Request $request): void
    {
        // Enforce hospital maintenance mode checks
        \App\Core\Middleware\MaintenanceMiddleware::handle($request);

        $method = $request->getMethod();
        $path = $request->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches)) {
                // Extract named parameter values
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setParams($params);

                // Run route middlewares
                $this->executeMiddlewares($route['middlewares'], $request);

                // Execute handler
                $response = $this->callHandler($route['handler'], $request, $params);

                if ($response instanceof Response) {
                    $response->send();
                } elseif (is_string($response)) {
                    (new Response($response))->send();
                }
                return;
            }
        }

        // 404 Route Not Found
        $this->handleNotFound($request);
    }

    private function executeMiddlewares(array $middlewares, Request $request): void
    {
        foreach ($middlewares as $middleware) {
            if ($middleware === 'csrf') {
                Csrf::verifyRequest($request);
            } elseif ($middleware === 'auth') {
                \App\Core\Middleware\AuthMiddleware::handle($request);
            } elseif ($middleware === 'guest') {
                \App\Core\Middleware\GuestMiddleware::handle($request);
            } elseif ($middleware === 'admin') {
                \App\Core\Middleware\AdminMiddleware::handle($request);
            } elseif ($middleware === 'supervisor') {
                \App\Core\Middleware\SupervisorMiddleware::handle($request);
            }
        }
    }

    private function callHandler(array|callable $handler, Request $request, array $params): mixed
    {
        if (is_callable($handler)) {
            return call_user_func($handler, $request, ...array_values($params));
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;

            if (!class_exists($class)) {
                throw new \RuntimeException("Controller class '{$class}' does not exist.", 500);
            }

            $controller = new $class();

            if (!method_exists($controller, $method)) {
                throw new \RuntimeException("Method '{$method}' not found on controller '{$class}'.", 500);
            }

            return $controller->$method($request, ...array_values($params));
        }

        throw new \RuntimeException('Invalid route handler configuration.', 500);
    }

    private function handleNotFound(Request $request): void
    {
        Logger::notice("Route 404 Not Found: {$request->getMethod()} {$request->getPath()}", [
            'ip' => $request->getIp(),
            'uri' => $request->getUri(),
        ]);

        throw new \RuntimeException("The requested endpoint '{$request->getPath()}' was not found on this hospital server.", 404);
    }
}
