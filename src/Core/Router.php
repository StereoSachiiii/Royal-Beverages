<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    /** @var array<string, array<int, array{pattern:string, paramNames:array, handler:callable|array}>> */
    private array $routes = [];

    /** @var string */
    private string $currentGroupPrefix = '';

    /** @var \App\DIContainer\Container|null */
    private ?\App\DIContainer\Container $container = null;

    /** @var \App\DIContainer\Container|null */
    private static ?\App\DIContainer\Container $instanceContainer = null;

    public function __construct(?\App\DIContainer\Container $container = null)
    {
        $this->container = $container;
        if ($container !== null) {
            self::$instanceContainer = $container;
        }
    }

    public static function getContainer(): ?\App\DIContainer\Container
    {
        return self::$instanceContainer;
    }

    public function get(string $path, callable|array $handler): Route
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): Route
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): Route
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): Route
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function group(string $prefix, callable $callback): void
    {
        $previous = $this->currentGroupPrefix;
        $this->currentGroupPrefix = rtrim($previous . $prefix, '/');
        if ($this->currentGroupPrefix === '') {
            $this->currentGroupPrefix = '/';
        }

        $callback($this);

        $this->currentGroupPrefix = $previous;
    }

    public function dispatch(Request $request): mixed
    {
        $method = strtoupper($request->getMethod());
        $uri    = $request->getUri();

        $routesForMethod = $this->routes[$method] ?? [];

        foreach ($routesForMethod as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = [];
                foreach ($route['paramNames'] as $name) {
                    if (isset($matches[$name])) {
                        $params[$name] = $matches[$name];
                    }
                }

                $handler = $route['handler'];
                $routeMiddleware = $route['middleware'] ?? [];

                if (!empty($routeMiddleware)) {
                    $stack = new MiddlewareStack();
                    foreach ($routeMiddleware as $mw) {
                        $stack->add($mw);
                    }
                    return $stack->process($request, function (Request $req) use ($handler, $params) {
                        return $this->executeHandler($handler, $req, $params);
                    });
                }

                return $this->executeHandler($handler, $request, $params);
            }
        }

        return null;
    }

    private function executeHandler(callable|array $handler, Request $request, array $params): mixed
    {
        // Resolve string controller names from the container if available
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
            if ($this->container && $this->container->has($handler[0])) {
                $handler[0] = $this->container->get($handler[0]);
            }
        }

        if ($handler instanceof \Closure) {
            $ref = new \ReflectionFunction($handler);
            $paramCount = $ref->getNumberOfParameters();
            if ($paramCount >= 2) {
                return $handler($request, $params);
            }
            return $handler($request);
        } elseif (is_array($handler) && count($handler) === 2) {
            $ref = new \ReflectionMethod($handler[0], $handler[1]);
            $args = [];
            foreach ($ref->getParameters() as $param) {
                $name = $param->getName();
                $type = $param->getType();
                $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

                if ($typeName === \App\Core\Request::class) {
                    $args[] = $request;
                } elseif (isset($params[$name])) {
                    $val = $params[$name];
                    if ($typeName === 'int') {
                        $args[] = (int)$val;
                    } elseif ($typeName === 'float') {
                        $args[] = (float)$val;
                    } elseif ($typeName === 'bool') {
                        $args[] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                    } else {
                        $args[] = $val;
                    }
                } else {
                    // Check request query parameters or request body (if it exists)
                    $queryVal = $request->getQuery($name);
                    if ($queryVal === null && method_exists($request, 'getBody')) {
                        $queryVal = $request->getBody($name);
                    }
                    if ($queryVal === null && method_exists($request, 'getAllBody')) {
                        $body = $request->getAllBody();
                        if (isset($body[$name])) {
                            $queryVal = $body[$name];
                        }
                    }

                    if ($queryVal !== null) {
                        if ($typeName === 'int') {
                            $args[] = (int)$queryVal;
                        } elseif ($typeName === 'float') {
                            $args[] = (float)$queryVal;
                        } elseif ($typeName === 'bool') {
                            $args[] = filter_var($queryVal, FILTER_VALIDATE_BOOLEAN);
                        } elseif ($typeName === 'array' && is_string($queryVal)) {
                            $args[] = json_decode($queryVal, true) ?? [$queryVal];
                        } else {
                            $args[] = $queryVal;
                        }
                    } elseif ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        $args[] = null;
                    }
                }
            }
            return call_user_func_array($handler, $args);
        } else {
            // Fallback: call with (Request, params)
            return $handler($request, $params);
        }
    }

    private function addRoute(string $method, string $path, callable|array $handler): Route
    {
        $method = strtoupper($method);

        $fullPath = $this->normalizePath($this->currentGroupPrefix, $path);

        // Convert path like /api/v1/products/:id to regex with named group
        $paramNames = [];
        $pattern = preg_replace_callback('#:([a-zA-Z_][a-zA-Z0-9_]*)#', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $fullPath);

        $regex = '#^' . $pattern . '$#';

        $routeData = [
            'pattern'    => $regex,
            'paramNames' => $paramNames,
            'handler'    => $handler,
            'middleware' => [],
        ];

        $this->routes[$method][] = &$routeData;
        $lastIdx = count($this->routes[$method]) - 1;
        return new Route($this->routes[$method][$lastIdx]);
    }

    private function normalizePath(string $prefix, string $path): string
    {
        $prefix = rtrim($prefix, '/');
        $path   = '/' . ltrim($path, '/');

        if ($prefix === '' || $prefix === '/') {
            return $path;
        }

        return $prefix . $path;
    }
}
