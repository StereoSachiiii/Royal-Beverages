<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\AdminViewController;
use App\Admin\Middleware\RateLimitMiddleware;
use App\Admin\Middleware\AuthMiddleware;
use App\Admin\Middleware\CSRFMiddleware;
use App\Core\Router;


// Guard against direct access - must be loaded via router
if (!isset($router)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Direct access not allowed.']);
    exit;
}

$router->group('/api/v1', function (Router $router): void {
    // GET /api/v1/admin/views/dashboard (dashboard stats)
    $router->get('/admin/views/dashboard', function (Request $request): array {
        $controller = $GLOBALS['container']->get(AdminViewController::class);
        return $controller->getDashboardStats();
    
    })->middleware([
        new AuthMiddleware(true),
        new RateLimitMiddleware('admin_view', 100, 60)
    ]);

    // GET /api/v1/admin/views/:entity/:id (detail view)
    $router->get('/admin/views/:entity/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(AdminViewController::class);
        $entity     = $params['entity'] ?? null;
        $id         = (int)($params['id'] ?? 0);
        if ($entity === null) {
            return [
                'success' => false,
                'message' => 'Entity parameter required',
                'code'    => 400,
            ];
        }
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Valid ID required',
                'code'    => 400,
            ];
        }
        return $controller->getDetail($entity, $id);
    
    })->middleware([
        new AuthMiddleware(true),
        new RateLimitMiddleware('admin_view', 100, 60)
    ]);

    // GET /api/v1/admin/views/:entity (list view)
    $router->get('/admin/views/:entity', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(AdminViewController::class);
        $entity     = $params['entity'] ?? null;
        if ($entity === null) {
            return [
                'success' => false,
                'message' => 'Entity parameter required',
                'code'    => 400,
            ];
        }
        $limit  = (int)min(100, max(1, (int)$request->getQuery('limit', 50)));
        $offset = (int)max(0, (int)$request->getQuery('offset', 0));
        $search = $request->getQuery('search');
        return $controller->getList($entity, $limit, $offset, $search);
    
    })->middleware([
        new AuthMiddleware(true),
        new RateLimitMiddleware('admin_view', 100, 60)
    ]);
});
