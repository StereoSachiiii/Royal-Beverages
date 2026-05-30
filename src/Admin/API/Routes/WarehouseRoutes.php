<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\WarehouseController;
use App\Admin\Middleware\RateLimitMiddleware;
use App\Admin\Middleware\AuthMiddleware;
use App\Admin\Middleware\CSRFMiddleware;
use App\Core\Router;

if (!defined('ROOT_PATH')) {
    http_response_code(400);
    exit;
}

/** @var \App\Core\Router $router */


/** @var Router $router */




$router->group('/api/v1', function (Router $router): void {
    // List warehouses (with optional includeInactive)
    $router->get('/warehouses', function (Request $request): array {
        $controller = $GLOBALS['container']->get(WarehouseController::class);
        $limit           = (int)$request->getQuery('limit', 50);
        $offset          = (int)$request->getQuery('offset', 0);
        $includeInactive = $request->getQuery('includeInactive') === 'true';
        if ($includeInactive) {
            return $controller->getAllIncludingInactive($limit, $offset);
        }
        return $controller->getAll($limit, $offset);
    
    })->middleware([
        new AuthMiddleware(true)
    ]);

    // Get warehouse by ID
    $router->get('/warehouses/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(WarehouseController::class);
        $id         = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Warehouse ID required',
                'code'    => 400,
            ];
        }
        return $controller->getById($id);
    });

    // Get warehouse by name
    $router->get('/warehouses/by-name', function (Request $request): array {
        $controller = $GLOBALS['container']->get(WarehouseController::class);
        $name       = (string)$request->getQuery('name', '');
        return $controller->getByName($name);
    });

    // Search warehouses
    $router->get('/warehouses/search', function (Request $request): array {
        $controller = $GLOBALS['container']->get(WarehouseController::class);
        $query      = (string)$request->getQuery('search', '');
        $limit      = (int)$request->getQuery('limit', 50);
        $offset     = (int)$request->getQuery('offset', 0);
        return $controller->search($query, $limit, $offset);
    });

    // Count warehouses
    $router->get('/warehouses/count', function (Request $request): array {
        $controller = $GLOBALS['container']->get(WarehouseController::class);
        $includeInactive = $request->getQuery('includeInactive') === 'true';
        if ($includeInactive) {
            return $controller->countAll();
        }
        return $controller->count();
    
    })->middleware([
        new AuthMiddleware(true)
    ]);

    // Create warehouse
    $router->post('/warehouses', function (Request $request): array {
        $controller = $GLOBALS['container']->get(WarehouseController::class);
        $body       = $request->getAllBody();
        return $controller->create($body);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('warehouse_create', 5, 60)
    ]);

    // Update or partial update warehouse
    $router->put('/warehouses/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(WarehouseController::class);
        $body       = $request->getAllBody();
        $id         = (int)($params['id'] ?? ($body['id'] ?? 0));
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Warehouse ID required',
                'code'    => 400,
            ];
        }
        $partial = $request->getQuery('partial') === 'true';
        return $partial
            ? $controller->partialUpdate($id, $body)
            : $controller->update($id, $body);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('warehouse_update', 5, 60)
    ]);

    // Delete / hard delete warehouse
    $router->delete('/warehouses/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(WarehouseController::class);
        $id         = (int)($params['id'] ?? 0);
        $hard       = $request->getQuery('hard') === 'true';
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Warehouse ID required',
                'code'    => 400,
            ];
        }
        return $hard ? $controller->hardDelete($id) : $controller->delete($id);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('warehouse_delete', 5, 60)
    ]);
});
