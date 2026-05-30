<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\SupplierController;
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
    // List suppliers (with optional search / includeInactive)
    $router->get('/suppliers', function (Request $request): array {
        $controller = $GLOBALS['container']->get(SupplierController::class);
        $limit           = (int)$request->getQuery('limit', 50);
        $offset          = (int)$request->getQuery('offset', 0);
        $includeInactive = $request->getQuery('includeInactive') === 'true';
        $search          = trim((string)$request->getQuery('search', ''));
        // Search if query provided
        if ($search !== '') {
            return $controller->search($search, $limit, $offset);
        }
        if ($includeInactive) {
            return $controller->getAllIncludingInactive($limit, $offset);
        }
        return $controller->getAll($limit, $offset);
    
    })->middleware([
        new AuthMiddleware(true)
    ]);

    // Get supplier by ID
    $router->get('/suppliers/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(SupplierController::class);
        $id         = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Supplier ID required',
                'code'    => 400,
            ];
        }
        return $controller->getById($id);
    });

    // Get supplier by name
    $router->get('/suppliers/by-name', function (Request $request): array {
        $controller = $GLOBALS['container']->get(SupplierController::class);
        $name       = (string)$request->getQuery('name', '');
        return $controller->getByName($name);
    });

    // Search suppliers
    $router->get('/suppliers/search', function (Request $request): array {
        $controller = $GLOBALS['container']->get(SupplierController::class);
        $query      = (string)$request->getQuery('search', '');
        $limit      = (int)$request->getQuery('limit', 50);
        $offset     = (int)$request->getQuery('offset', 0);
        return $controller->search($query, $limit, $offset);
    });

    // Count suppliers
    $router->get('/suppliers/count', function (Request $request): array {
        $controller = $GLOBALS['container']->get(SupplierController::class);
        $includeInactive = $request->getQuery('includeInactive') === 'true';
        if ($includeInactive) {
            return $controller->countAll();
        }
        return $controller->count();
    
    })->middleware([
        new AuthMiddleware(true)
    ]);

    // Create supplier
    $router->post('/suppliers', function (Request $request): array {
        $controller = $GLOBALS['container']->get(SupplierController::class);
        $body       = $request->getAllBody();
        return $controller->create($body);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('supplier_create', 5, 60)
    ]);

    // Update supplier
    $router->put('/suppliers/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(SupplierController::class);
        $body       = $request->getAllBody();
        $id         = (int)($params['id'] ?? ($body['id'] ?? 0));
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Supplier ID required',
                'code'    => 400,
            ];
        }
        return $controller->update($id, $body);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('supplier_update', 5, 60)
    ]);

    // Delete / hard delete supplier
    $router->delete('/suppliers/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(SupplierController::class);
        $id         = (int)($params['id'] ?? 0);
        $hard       = $request->getQuery('hard') === 'true';
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Supplier ID required',
                'code'    => 400,
            ];
        }
        return $hard ? $controller->hardDelete($id) : $controller->delete($id);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('supplier_delete', 5, 60)
    ]);
});
