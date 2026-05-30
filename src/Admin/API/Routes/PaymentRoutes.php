<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\PaymentController;
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
    // Search payments - MUST be before :id routes
    $router->get('/payments/search', function (Request $request): array {
        $controller = $GLOBALS['container']->get(PaymentController::class);
        $query  = (string)$request->getQuery('search', '');
        $limit  = (int)$request->getQuery('limit', 50);
        $offset = (int)$request->getQuery('offset', 0);
        return $controller->search($query, $limit, $offset);
    
    })->middleware([
        new AuthMiddleware(true),
        new RateLimitMiddleware('payment_search', 30, 60)
    ]);

    // Admin list of payments
    $router->get('/payments', function (Request $request): array {
        $controller = $GLOBALS['container']->get(PaymentController::class);
        $limit  = (int)$request->getQuery('limit', 50);
        $offset = (int)$request->getQuery('offset', 0);
        return $controller->getAll($limit, $offset);
    
    })->middleware([
        new AuthMiddleware(true)
    ]);

    // Get payment by ID
    $router->get('/payments/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(PaymentController::class);
        $id         = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Payment ID required',
                'code'    => 400,
            ];
        }
        return $controller->getById($id);
    
    })->middleware([
        new AuthMiddleware(true)
    ]);

    // Get payments by order ID
    $router->get('/payments/order/:order_id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(PaymentController::class);
        $orderId    = (int)($params['order_id'] ?? 0);
        return $controller->getByOrder($orderId);
    
    })->middleware([
        new AuthMiddleware(true)
    ]);

    // Count payments
    $router->get('/payments/count', function (Request $request): array {
        $controller = $GLOBALS['container']->get(PaymentController::class);
        return $controller->count();
    
    })->middleware([
        new AuthMiddleware(true)
    ]);

    // Create payment
    $router->post('/payments', function (Request $request): array {
        $controller = $GLOBALS['container']->get(PaymentController::class);
        $body       = $request->getAllBody();
        return $controller->create($body);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('payment_create', 5, 60)
    ]);

    // Update payment
    $router->put('/payments/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(PaymentController::class);
        $body       = $request->getAllBody();
        $id         = (int)($params['id'] ?? ($body['id'] ?? 0));
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Payment ID required',
                'code'    => 400,
            ];
        }
        return $controller->update($id, $body);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('payment_update', 5, 60)
    ]);

    // Delete / hard-delete payment
    $router->delete('/payments/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(PaymentController::class);
        $id         = (int)($params['id'] ?? 0);
        $hard       = $request->getQuery('hard') === 'true';
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Payment ID required',
                'code'    => 400,
            ];
        }
        if ($hard) {
            return $controller->hardDelete($id);
        }
        return $controller->delete($id);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('payment_delete', 5, 60),
        new RateLimitMiddleware('payment_hard_delete', 2, 60)
    ]);
});
