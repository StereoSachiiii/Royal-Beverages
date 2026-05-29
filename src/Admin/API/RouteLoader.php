<?php
declare(strict_types=1);

namespace App\Admin\API;

use App\Core\Router;

/**
 * RouteLoader
 *
 * Automatically discovers and includes all Admin API route definition files.
 */
class RouteLoader
{
    /**
     * Load all modules into the provided router
     * 
     * @param Router $router The router instance to register routes on
     */
    public static function load(Router $router): void
    {
        $routesDir = __DIR__ . '/Routes';
        
        // Scan the directory dynamically for all php files
        $files = glob($routesDir . '/*.php');
        
        if (is_array($files)) {
            foreach ($files as $path) {
                // We use require instead of require_once to ensure the 
                // $router variable is available in the route file's scope
                require $path;
            }
        }
    }
}
