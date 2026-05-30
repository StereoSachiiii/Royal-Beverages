<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\OrderItemController;
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
    // GET /api/v1/order-items (with optional filters)
    $router->get('/order-items', function (Request $request): array {
        $controller = $GLOBALS['container']->get(OrderItemController::class);
        $id       = $request->getQuery('id');
        $orderId  = $request->getQuery('order_id');
        $count    = $request->getQuery('count');
        if ($id !== null) {
            $id = (int)$id;
            if ($id <= 0) {
                return [
                    'success' => false,
                    'message' => 'Order item ID required',
                    'code'    => 400,
                ];
            }
            return $controller->getById($id);
        }
        if ($orderId !== null) {
            $orderId = (int)$orderId;
            return $controller->getByOrder($orderId);
        }
        if ($count === 'true') {
            return $controller->count();
        }
        $limit  = (int)$request->getQuery('limit', 50);
        $offset = (int)$request->getQuery('offset', 0);
        return $controller->getAll($limit, $offset);
    
    })->middleware([
        new AuthMiddleware(true)
    ]);

    // GET /api/v1/order-items/:id
    $router->get('/order-items/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(OrderItemController::class);
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Order item ID required',
                'code'    => 400,
            ];
        }
        return $controller->getById($id);
    });

    // POST /api/v1/order-items
    $router->post('/order-items', function (Request $request): array {
        $controller = $GLOBALS['container']->get(OrderItemController::class);
        $body       = $request->getAllBody();
        return $controller->create($body);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('order_item_create', 20, 60)
    ]);

    // PUT /api/v1/order-items/:id
    $router->put('/order-items/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(OrderItemController::class);
        $body       = $request->getAllBody();
        $id         = (int)($params['id'] ?? ($body['id'] ?? 0));
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Order item ID required',
                'code'    => 400,
            ];
        }
        return $controller->update($id, $body);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('order_item_update', 20, 60)
    ]);

    // DELETE /api/v1/order-items/:id
    $router->delete('/order-items/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(OrderItemController::class);
        $id         = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Order item ID required',
                'code'    => 400,
            ];
        }
        return $controller->delete($id);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('order_item_delete', 10, 60)
    ]);
});
