<?php
declare(strict_types=1);

namespace App\Core;

use App\Interfaces\MiddlewareInterface;

/**
 * Middleware Stack
 * 
 * Manages and executes middleware chain
 */
class MiddlewareStack
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $middleware = [];

    /**
     * Add middleware to the stack
     *
     * @param MiddlewareInterface $middleware
     * @return void
     */
    public function add(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Process the middleware stack
     *
     * @param Request $request
     * @param callable $finalHandler The final handler (usually the controller)
     * @return mixed
     */
    public function process(Request $request, callable $finalHandler): mixed
    {
        // Create a chain of middleware
        $next = $this->createNext($this->middleware, $finalHandler);
        
        // Start the chain
        return $next($request);
    }

    /**
     * Create the next callable in the chain
     *
     * @param array $middleware Array of middleware
     * @param callable $finalHandler
     * @return callable
     */
    private function createNext(array $middleware, callable $finalHandler): callable
    {
        // If no middleware left, return the final handler
        if (empty($middleware)) {
            return $finalHandler;
        }

        // Get the first middleware
        $current = array_shift($middleware);

        // Create a callable that will execute the current middleware
        // and pass along the next one in the chain
        return function (Request $request) use ($current, $middleware, $finalHandler) {
            return $current->handle(
                $request,
                $this->createNext($middleware, $finalHandler)
            );
        };
    }

    /**
     * Get all middleware in the stack
     *
     * @return MiddlewareInterface[]
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Clear all middleware from the stack
     *
     * @return void
     */
    public function clear(): void
    {
        $this->middleware = [];
    }
}
