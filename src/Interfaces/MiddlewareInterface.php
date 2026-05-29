<?php
declare(strict_types=1);

namespace App\Interfaces;

use App\Core\Request;

/**
 * Middleware Interface
 * 
 * All middleware must implement this interface
 */
interface MiddlewareInterface
{
    /**
     * Handle the request and pass to the next middleware
     *
     * @param Request $request
     * @param callable $next The next middleware in the chain
     * @return mixed
     */
    public function handle(Request $request, callable $next): mixed;
}
