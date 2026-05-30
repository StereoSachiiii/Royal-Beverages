<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\UserPreferenceController;
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
    // GET /api/v1/user-preferences
    $router->get('/user-preferences', function (Request $request): array {
        $controller = $GLOBALS['container']->get(UserPreferenceController::class);
        $limit  = (int)$request->getQuery('limit', 50);
        $offset = (int)$request->getQuery('offset', 0);
        return $controller->getAll($limit, $offset);
    });

    // GET /api/v1/user-preferences/:id
    $router->get('/user-preferences/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(UserPreferenceController::class);
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'ID required',
                'code'    => 400,
            ];
        }
        return $controller->getById($id);
    
    })->middleware([
        new RateLimitMiddleware('user_pref_getById', 10, 60)
    ]);

    // GET /api/v1/user-preferences/user/:user_id
    $router->get('/user-preferences/user/:user_id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(UserPreferenceController::class);
        $userId = (int)($params['user_id'] ?? 0);
        return $controller->getByUserId($userId);
    
    })->middleware([
        new RateLimitMiddleware('user_pref_getByUserId', 10, 60)
    ]);

    // POST /api/v1/user-preferences
    $router->post('/user-preferences', function (Request $request): array {
        $controller = $GLOBALS['container']->get(UserPreferenceController::class);
        $body   = $request->getAllBody();
        $result = $controller->create($body);
        $result['code'] = $result['code'] ?? 201;
        return $result;
    
    })->middleware([
        new RateLimitMiddleware('user_pref_create', 5, 60)
    ]);

    // PUT /api/v1/user-preferences/:id
    $router->put('/user-preferences/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(UserPreferenceController::class);
        $body = $request->getAllBody();
        $id   = (int)($params['id'] ?? ($body['id'] ?? 0));
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'ID required for update',
                'code'    => 400,
            ];
        }
        return $controller->update($id, $body);
    
    })->middleware([
        new RateLimitMiddleware('user_pref_update', 10, 60)
    ]);

    // DELETE /api/v1/user-preferences/:id
    $router->delete('/user-preferences/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(UserPreferenceController::class);
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'ID required for delete',
                'code'    => 400,
            ];
        }
        return $controller->delete($id);
    
    })->middleware([
        new RateLimitMiddleware('user_pref_delete', 5, 60)
    ]);
});
