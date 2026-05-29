<?php
declare(strict_types=1);

/**
 * Modern API Gateway (v1)
 * Handles all requests under /api/v1/
 */

require_once __DIR__ . '/../../../src/Core/bootstrap.php';

use App\Core\Request;
use App\Core\Router;
use App\Admin\Middleware\JsonMiddleware;
use App\Admin\Exceptions\BaseException;
use App\Admin\API\ApiServiceProvider;
use App\Admin\API\RouteLoader;

// 1. Initialize Container & Services
$container = new \App\DIContainer\Container();
$provider = new ApiServiceProvider($container);
$provider->register();

// Make container globally available for route handlers
$GLOBALS['container'] = $container;

// 2. Setup Headers & CORS
header('Content-Type: application/json; charset=utf-8');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (str_contains($origin, 'localhost')) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 3. Routing
$request = Request::createFromGlobals();

/**
 * Normalization for API Gateway:
 * Our route definition files (e.g. UserRoutes.php) use $router->group('/api/v1', ...)
 * but Request::createFromGlobals() might return a path relative to the index.php (e.g. /users/login)
 * or a root-relative path (e.g. /api/v1/users/login).
 * We must ensure the Router receives exactly /api/v1/... for matching.
 */
$uri = $request->getUri();
$apiPrefix = '/api/v1';

if (!str_starts_with($uri, $apiPrefix)) {
    // If it doesn't start with /api/v1, but we are in this gateway, it's a relative path
    $cleanUri = $apiPrefix . '/' . ltrim($uri, '/');
    $cleanUri = str_replace('//', '/', $cleanUri);
    
    // Update the Request object via reflection to ensure Router sees the normalized path
    $ref = new ReflectionClass($request);
    $prop = $ref->getProperty('uri');
    $prop->setAccessible(true);
    $prop->setValue($request, $cleanUri);
} else {
    // Already has the prefix, just ensure it's clean
    $cleanUri = '/' . ltrim($uri, '/');
    $cleanUri = rtrim($cleanUri, '/');
    if ($cleanUri === '') $cleanUri = '/';
}

$router  = new Router($container);

// Load namespaced routes
RouteLoader::load($router);

try {
    $result = $router->dispatch($request);

    // Route not found
    if ($result === null) {
        JsonMiddleware::sendResponse([
            'success' => false,
            'message' => 'Route not found: ' . $request->getUri(),
            'code'    => 404,
        ], 404);
    }

    // Normalize result into standard API structure
    if (is_array($result)) {
        $normalized = [
            'success' => $result['success'] ?? true,
            'message' => $result['message'] ?? 'OK',
            'data'    => $result['data']    ?? ($result['success'] ?? true ? ($result['data'] ?? $result) : null),
            'errors'  => $result['errors']  ?? [],
            'code'    => isset($result['code']) && is_int($result['code']) ? $result['code'] : 200,
        ];
        
        JsonMiddleware::sendResponse($normalized, (int)$normalized['code']);
    }

    // Fallback for non-array results
    JsonMiddleware::sendResponse([
        'success' => true,
        'message' => 'OK',
        'data'    => $result,
        'code'    => 200,
    ], 200);

} catch (Throwable $e) {
    $code = ($e instanceof BaseException) ? $e->getStatusCode() : 500;
    $code = ($code >= 400 && $code < 600) ? $code : 500;
    
    JsonMiddleware::sendResponse([
        'success' => false,
        'message' => $e->getMessage(),
        'errors'  => ($e instanceof BaseException) ? $e->getContext() : [],
        'code'    => $code,
        'trace'   => defined('DEBUG') && DEBUG ? $e->getTraceAsString() : null
    ], (int)$code);
}
