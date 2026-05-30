<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\FeedbackController;
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
    // GET /api/v1/feedback (list all with enriched data)
    $router->get('/feedback', function (Request $request): array {
        $controller = $GLOBALS['container']->get(FeedbackController::class);
        $limit  = (int)$request->getQuery('limit', 50);
        $offset = (int)$request->getQuery('offset', 0);
        $isActive = $request->getQuery('isActive') !== null ? $request->getQuery('isActive') === 'true' : null;
        // Always use paginated/enriched version for admin
        return $controller->getAllPaginated($limit, $offset, $isActive);
    
    })->middleware([
        new RateLimitMiddleware('feedback_getAll', 5, 60)
    ]);

    // GET /api/v1/feedback/paginated
    $router->get('/feedback/paginated', function (Request $request): array {
        $controller = $GLOBALS['container']->get(FeedbackController::class);
        $limit      = (int)$request->getQuery('limit', 50);
        $offset     = (int)$request->getQuery('offset', 0);
        $isActive   = $request->getQuery('isActive') !== null ? $request->getQuery('isActive') === 'true' : null;
        return $controller->getAllPaginated($limit, $offset, $isActive);
    
    })->middleware([
        new RateLimitMiddleware('feedback_getAllPaginated', 5, 60)
    ]);

    // GET /api/v1/feedback/:id
    $router->get('/feedback/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(FeedbackController::class);
        $id         = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'ID required',
                'code'    => 400,
            ];
        }
        return $controller->getById($id);
    
    })->middleware([
        new RateLimitMiddleware('feedback_getById', 10, 60)
    ]);

    // GET /api/v1/feedback/product/:product_id
    $router->get('/feedback/product/:product_id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(FeedbackController::class);
        $productId  = (int)($params['product_id'] ?? 0);
        return $controller->getByProductId($productId);
    
    })->middleware([
        new RateLimitMiddleware('feedback_getByProductId', 10, 60)
    ]);

    // GET /api/v1/feedback/user/:user_id
    $router->get('/feedback/user/:user_id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(FeedbackController::class);
        $userId     = (int)($params['user_id'] ?? 0);
        return $controller->getByUserId($userId);
    
    })->middleware([
        new RateLimitMiddleware('feedback_getByUserId', 10, 60)
    ]);

    // GET /api/v1/feedback/product/:product_id/avg-rating
    $router->get('/feedback/product/:product_id/avg-rating', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(FeedbackController::class);
        $productId  = (int)($params['product_id'] ?? 0);
        return $controller->getAverageRating($productId);
    
    })->middleware([
        new RateLimitMiddleware('feedback_getAvgRating', 10, 60)
    ]);

    // POST /api/v1/feedback
    $router->post('/feedback', function (Request $request): array {
        $controller = $GLOBALS['container']->get(FeedbackController::class);
        $body       = $request->getAllBody();
        $result     = $controller->create($body);
        $result['code'] = $result['code'] ?? 201;
        return $result;
    
    })->middleware([
        new RateLimitMiddleware('feedback_create', 5, 60)
    ]);

    // PUT /api/v1/feedback/:id
    $router->put('/feedback/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(FeedbackController::class);
        $body       = $request->getAllBody();
        $id         = (int)($params['id'] ?? ($body['id'] ?? 0));
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'ID required for update',
                'code'    => 400,
            ];
        }
        return $controller->update($id, $body);
    
    })->middleware([
        new RateLimitMiddleware('feedback_update', 10, 60)
    ]);

    // DELETE /api/v1/feedback/:id
    $router->delete('/feedback/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(FeedbackController::class);
        $body       = $request->getAllBody();
        $id         = (int)($params['id'] ?? ($body['id'] ?? 0));
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'ID required for delete',
                'code'    => 400,
            ];
        }
        $hard = $request->getQuery('hard') === 'true' || !empty($body['hard']);
        if ($hard) {
            return $controller->hardDelete($id);
        }
        return $controller->delete($id);
    
    })->middleware([
        new RateLimitMiddleware('feedback_delete', 5, 60)
    ]);
});
