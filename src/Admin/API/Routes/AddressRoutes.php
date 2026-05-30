<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\AddressController;
use App\Admin\Middleware\RateLimitMiddleware;
use App\Admin\Middleware\AuthMiddleware;
use App\Admin\Middleware\CsrfMiddleware;
use App\Core\Router;


// Guard against direct access - must be loaded via router
if (!isset($router)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Direct access not allowed.']);
    exit;
}

$router->group('/api/v1', function (Router $router): void {
    // GET /api/v1/addresses
    $router->get('/addresses', function (Request $request): array {
        $controller = $GLOBALS['container']->get(AddressController::class);
        $limit  = (int)$request->getQuery('limit', 50);
        $offset = (int)$request->getQuery('offset', 0);
        // Use enriched version for user name/email in table
        return $controller->getAllEnriched($limit, $offset);
    });

    // GET /api/v1/addresses/:id
    $router->get('/addresses/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(AddressController::class);
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Address ID required',
                'code'    => 400,
            ];
        }
        // Use enriched version for view modal
        return $controller->getByIdEnriched($id);
    });

    // GET /api/v1/addresses/user/:user_id
    $router->get('/addresses/user/:user_id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(AddressController::class);
        $userId = (int)($params['user_id'] ?? 0);
        return $controller->getByUser($userId);
    });

    // GET /api/v1/addresses/count
    $router->get('/addresses/count', function (Request $request): array {
        $controller = $GLOBALS['container']->get(AddressController::class);
        return $controller->count();
    });

    // POST /api/v1/addresses
    $router->post('/addresses', function (Request $request): array {
        $controller = $GLOBALS['container']->get(AddressController::class);
        $body = $request->getAllBody();
        return $controller->create($body);
    
    })->middleware([
        new RateLimitMiddleware('address_create', 10, 60)
    ]);

    // PUT /api/v1/addresses/:id
    $router->put('/addresses/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(AddressController::class);
        $body = $request->getAllBody();
        $id = (int)($params['id'] ?? ($body['id'] ?? 0));
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Address ID required',
                'code'    => 400,
            ];
        }
        return $controller->update($id, $body);
    
    })->middleware([
        new RateLimitMiddleware('address_update', 10, 60)
    ]);

    // DELETE /api/v1/addresses/:id
    $router->delete('/addresses/:id', function (Request $request, array $params): array {
        $controller = $GLOBALS['container']->get(AddressController::class);
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Address ID required',
                'code'    => 400,
            ];
        }
        return $controller->delete($id);
    
    })->middleware([
        new RateLimitMiddleware('address_delete', 10, 60)
    ]);
});
