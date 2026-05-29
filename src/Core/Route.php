<?php
declare(strict_types=1);

namespace App\Core;

class Route
{
    private array $routeRef;

    /**
     * Constructor
     *
     * @param array $routeRef Reference to the route array in Router
     */
    public function __construct(array &$routeRef)
    {
        // Bind by reference so modifications persist inside Router
        $this->routeRef = &$routeRef;
        if (!isset($this->routeRef['middleware'])) {
            $this->routeRef['middleware'] = [];
        }
    }

    /**
     * Add middleware to the route
     *
     * @param mixed $middleware A single middleware or array of middlewares
     * @return self
     */
    public function middleware(mixed $middleware): self
    {
        if (is_array($middleware)) {
            $this->routeRef['middleware'] = array_merge($this->routeRef['middleware'], $middleware);
        } else {
            $this->routeRef['middleware'][] = $middleware;
        }
        return $this;
    }
}
