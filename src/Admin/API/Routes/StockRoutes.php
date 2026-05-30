<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\StockController;
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
    // Available stock for a product
    $router->get('/stock/available/:product_id', function (Request $request, array $params) {
        $productId  = (int)($params['product_id'] ?? 0);
        return $GLOBALS['container']->get(StockController::class)->getAvailableStock($productId);
    });

    // Stock summary for a product
    $router->get('/stock/summary/:product_id', function (Request $request, array $params) {
        $productId  = (int)($params['product_id'] ?? 0);
        return $GLOBALS['container']->get(StockController::class)->getStockSummary($productId);
    });

    // All stock entries (paginated, with optional search)
    $router->get('/stock', function (Request $request) {
        $limit  = (int)$request->getQuery('limit', 50);
        $offset = (int)$request->getQuery('offset', 0);
        $search = trim((string)$request->getQuery('search', ''));
        // Use search method if query provided
        if ($search !== '') {
            return $GLOBALS['container']->get(StockController::class)->search($search, $limit, $offset);
        }
        return $GLOBALS['container']->get(StockController::class)->getAll($limit, $offset);
    });

    // Get by ID (enriched with product/warehouse names)
    $router->get('/stock/:id', function (Request $request, array $params) {
        $id         = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Stock ID required',
                'code'    => 400,
            ];
        }
        return $GLOBALS['container']->get(StockController::class)->getByIdEnriched($id);
    });

    // Get by product AND warehouse
    $router->get('/stock/product/:product_id/warehouse/:warehouse_id', function (Request $request, array $params) {
        $productId    = (int)($params['product_id'] ?? 0);
        $warehouseId  = (int)($params['warehouse_id'] ?? 0);
        return $GLOBALS['container']->get(StockController::class)->getByProductWarehouse($productId, $warehouseId);
    });

    // Get by product only
    $router->get('/stock/product/:product_id', function (Request $request, array $params) {
        $productId  = (int)($params['product_id'] ?? 0);
        return $GLOBALS['container']->get(StockController::class)->getByProduct($productId);
    });

    // Get by warehouse only
    $router->get('/stock/warehouse/:warehouse_id', function (Request $request, array $params) {
        $warehouseId = (int)($params['warehouse_id'] ?? 0);
        return $GLOBALS['container']->get(StockController::class)->getByWarehouse($warehouseId);
    });

    // Count stock rows
    $router->get('/stock/count', function (Request $request) {
        return $GLOBALS['container']->get(StockController::class)->count();
    });

    // ORDER OPERATIONS (reserve/confirm/cancel/refund)
    $router->post('/stock/orders/:order_id/:action', function (Request $request, array $params) {
        $orderId    = (int)($params['order_id'] ?? 0);
        $action     = (string)($params['action'] ?? '');
        if ($orderId <= 0) {
            return [
                'success' => false,
                'message' => 'Valid order ID required',
                'code'    => 400,
            ];
        }
        return match ($action) {
            'reserve' => $GLOBALS['container']->get(StockController::class)->reserveStock($orderId),
            'confirm' => $GLOBALS['container']->get(StockController::class)->confirmPayment($orderId),
            'cancel'  => $GLOBALS['container']->get(StockController::class)->cancelOrder($orderId),
            'refund'  => $GLOBALS['container']->get(StockController::class)->refundOrder($orderId),
            default   => [
                'success' => false,
                'message' => 'Invalid action. Use: reserve, confirm, cancel, refund',
                'code'    => 400,
            ],
        };
    
    })->middleware([
        new CSRFMiddleware(),
        new RateLimitMiddleware('stock_order_action', 10, 60)
    ]);

    // Warehouse transfer
    $router->post('/stock/transfer', function (Request $request) {
        $body       = $request->getAllBody();
        if (!isset($body['product_id'], $body['from_warehouse_id'], $body['to_warehouse_id'], $body['quantity'])) {
            return [
                'success' => false,
                'message' => 'product_id, from_warehouse_id, to_warehouse_id, and quantity required',
                'code'    => 400,
            ];
        }
        $productId       = (int)$body['product_id'];
        $fromWarehouseId = (int)$body['from_warehouse_id'];
        $toWarehouseId   = (int)$body['to_warehouse_id'];
        $quantity        = (int)$body['quantity'];
        $reason          = $body['reason'] ?? null;
        return $GLOBALS['container']->get(StockController::class)->transferStock($productId, $fromWarehouseId, $toWarehouseId, $quantity, $reason);
    
    })->middleware([
        new AuthMiddleware(true),
        new RateLimitMiddleware('stock_transfer', 10, 60)
    ]);

    // Stock adjustment
    $router->post('/stock/adjust', function (Request $request) {
        $body       = $request->getAllBody();
        if (!isset($body['product_id'], $body['warehouse_id'], $body['adjustment'])) {
            return [
                'success' => false,
                'message' => 'product_id, warehouse_id, and adjustment required',
                'code'    => 400,
            ];
        }
        $productId   = (int)$body['product_id'];
        $warehouseId = (int)$body['warehouse_id'];
        $adjustment  = (int)$body['adjustment'];
        $reason      = $body['reason'] ?? null;
        return $GLOBALS['container']->get(StockController::class)->adjustStock($productId, $warehouseId, $adjustment, $reason);
    
    })->middleware([
        new AuthMiddleware(true),
        new RateLimitMiddleware('stock_adjust', 10, 60)
    ]);

    // Regular stock creation (admin only)
    $router->post('/stock', function (Request $request) {
        $body       = $request->getAllBody();
        return $GLOBALS['container']->get(StockController::class)->create($body);
    
    })->middleware([
        new AuthMiddleware(true),
        new RateLimitMiddleware('stock_create', 5, 60)
    ]);

    // Update stock
    $router->put('/stock/:id', function (Request $request, array $params) {
        $body       = $request->getAllBody();
        $id         = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Stock ID required',
                'code'    => 400,
            ];
        }
        return $GLOBALS['container']->get(StockController::class)->update($id, $body);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('stock_update', 5, 60)
    ]);

    // Update by product + warehouse
    $router->put('/stock/product/:product_id/warehouse/:warehouse_id', function (Request $request, array $params) {
        $body         = $request->getAllBody();
        $productId    = (int)($params['product_id'] ?? 0);
        $warehouseId  = (int)($params['warehouse_id'] ?? 0);
        return $GLOBALS['container']->get(StockController::class)->updateByProductWarehouse($productId, $warehouseId, $body);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('stock_update', 5, 60)
    ]);

    // Delete by ID or product+warehouse
    $router->delete('/stock/:id', function (Request $request, array $params) {
        $id         = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Stock ID required',
                'code'    => 400,
            ];
        }
        return $GLOBALS['container']->get(StockController::class)->delete($id);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('stock_delete', 5, 60)
    ]);

    $router->delete('/stock/product/:product_id/warehouse/:warehouse_id', function (Request $request, array $params) {
        $productId    = (int)($params['product_id'] ?? 0);
        $warehouseId  = (int)($params['warehouse_id'] ?? 0);
        return $GLOBALS['container']->get(StockController::class)->deleteByProductWarehouse($productId, $warehouseId);
    
    })->middleware([
        new AuthMiddleware(true),
        new CSRFMiddleware(),
        new RateLimitMiddleware('stock_delete', 5, 60)
    ]);
});
