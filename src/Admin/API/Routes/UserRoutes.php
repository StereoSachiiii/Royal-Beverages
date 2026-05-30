<?php
declare(strict_types=1);

namespace App\Admin\API\Routes;

use App\Core\Request;
use App\Core\Session;
use App\Admin\Controllers\UserController;
use App\Admin\Middleware\RateLimitMiddleware;
use App\Admin\Middleware\AuthMiddleware;
use App\Admin\Middleware\CSRFMiddleware;
use App\Core\Router;

$router->group('/api/v1', function (Router $router): void {
    // ---------------------------------------------------------------------
    // AUTH & PUBLIC
    // ---------------------------------------------------------------------

    // POST /api/v1/users/register
    $router->post('/users/register', function (Request $request): array {
        RateLimitMiddleware::check('user_register', 10, 3600); // 10 per hour
        $controller = $GLOBALS['container']->get(UserController::class);
        $body       = $request->getAllBody();
        return $controller->register($body);
    });

    // POST /api/v1/users/login
    $router->post('/users/login', function (Request $request): array {
        RateLimitMiddleware::check('user_login', 15, 900); // 15 per 15 mins
        $controller = $GLOBALS['container']->get(UserController::class);
        $body       = $request->getAllBody();
        return $controller->login($body);
    });

    // Alias for /auth/login (for legacy/cache compatibility)
    $router->post('/auth/login', function (Request $request): array {
        RateLimitMiddleware::check('user_login', 15, 900);
        $controller = $GLOBALS['container']->get(UserController::class);
        $body       = $request->getAllBody();
        return $controller->login($body);
    });

    // Aliases for register/logout
    $router->post('/auth/register', function (Request $request): array {
        $controller = $GLOBALS['container']->get(UserController::class);
        return $controller->register($request->getAllBody());
    });
    $router->post('/auth/logout', function (Request $request): array {
        Session::getInstance()->logout();
        return ['success' => true, 'message' => 'Logged out'];
    });

    // Google OAuth Routes
    $router->get('/auth/google/redirect', function (Request $request): array {
        $controller = $GLOBALS['container']->get(UserController::class);
        return $controller->googleRedirect();
    });

    $router->get('/auth/google/callback', function (Request $request): array {
        $controller = $GLOBALS['container']->get(UserController::class);
        // PHP extracts query parameters automatically into $_GET or we can use $request->getQuery()
        // Wait, Request class doesn't give us all queries as an array natively sometimes,
        // so we can safely construct an array of code/state
        $queryParams = [
            'code' => $request->getQuery('code'),
            'state' => $request->getQuery('state')
        ];
        return $controller->googleCallback($queryParams);
    });

    // GET /api/v1/users/session  (public session check)
    $router->get('/users/session', function (Request $request): array {
        $session = Session::getInstance();

        return [
            'success' => true,
            'data'    => [
                'logged_in'  => $session->isLoggedIn(),
                'is_admin'   => $session->isAdmin(),
                'user_id'    => $session->get('user_id'),
                'name'       => $session->get('name'),
                'email'      => $session->get('email'),
                'csrf_token' => $session->getCsrfInstance()->getToken(),
            ],
            'code'    => 200,
        ];
    });

    // ---------------------------------------------------------------------
    // AUTHENTICATED USER ROUTES
    // ---------------------------------------------------------------------

    // POST /api/v1/users/logout
    $router->post('/users/logout', function (Request $request): array {
        AuthMiddleware::requireAuth();
        CSRFMiddleware::verifyCsrf();
        RateLimitMiddleware::check('user_post', 30, 60);

        Session::getInstance()->logout();

        return [
            'success' => true,
            'message' => 'Logged out successfully',
            'code'    => 200,
        ];
    });

    // PUT /api/v1/users/profile
    $router->put('/users/profile', function (Request $request): array {
        AuthMiddleware::requireAuth();
        CSRFMiddleware::verifyCsrf();
        RateLimitMiddleware::check('user_post', 30, 60);

        $controller = $GLOBALS['container']->get(UserController::class);
        $body       = $request->getAllBody();
        $userId     = (int)($request->getQuery('id') ?? ($body['id'] ?? 0));

        return $controller->updateProfile($userId, $body);
    });

    // POST /api/v1/users/anonymize
    $router->post('/users/anonymize', function (Request $request): array {
        $userId = AuthMiddleware::requireAuth();
        CSRFMiddleware::verifyCsrf();
        RateLimitMiddleware::check('user_post', 30, 60);

        $controller = $GLOBALS['container']->get(UserController::class);
        $result     = $controller->anonymizeUser($userId);

        if (!empty($result['success'])) {
            Session::getInstance()->logout();
        }

        return $result;
    });

    // ---------------------------------------------------------------------
    // ADDRESSES
    // ---------------------------------------------------------------------

    // POST /api/v1/users/addresses
    $router->post('/users/addresses', function (Request $request): array {
        $userId = AuthMiddleware::requireAuth();
        CSRFMiddleware::verifyCsrf();
        RateLimitMiddleware::check('user_post', 30, 60);

        $controller = $GLOBALS['container']->get(UserController::class);
        $body       = $request->getAllBody();

        return $controller->createAddress($userId, $body);
    });

    // PUT /api/v1/users/addresses/{address_id}
    $router->put('/users/addresses/:address_id', function (Request $request, array $params): array {
        $userId = AuthMiddleware::requireAuth();
        CSRFMiddleware::verifyCsrf();
        RateLimitMiddleware::check('user_post', 30, 60);

        $addressId = (int)($params['address_id'] ?? 0);
        if ($addressId <= 0) {
            return [
                'success' => false,
                'message' => 'address_id is required',
                'code'    => 400,
            ];
        }

        $controller = $GLOBALS['container']->get(UserController::class);
        $body       = $request->getAllBody();

        return $controller->updateAddress($addressId, $body);
    });

    // DELETE /api/v1/users/addresses/{address_id}
    $router->delete('/users/addresses/:address_id', function (Request $request, array $params): array {
        AuthMiddleware::requireAuth();
        CSRFMiddleware::verifyCsrf();
        RateLimitMiddleware::check('user_delete', 20, 60);

        $addressId = (int)($params['address_id'] ?? 0);
        if ($addressId <= 0) {
            return [
                'success' => false,
                'message' => 'address_id is required',
                'code'    => 400,
            ];
        }

        $controller = $GLOBALS['container']->get(UserController::class);
        return $controller->deleteAddress($addressId);
    });

    // GET /api/v1/users/profile
    $router->get('/users/profile', function (Request $request): array {
        $userId = AuthMiddleware::requireAuth();
        RateLimitMiddleware::check('users_get', 60, 60);

        $controller = $GLOBALS['container']->get(UserController::class);
        return $controller->getProfile($userId);
    });

    // GET /api/v1/users/addresses
    $router->get('/users/addresses', function (Request $request): array {
        $userId = AuthMiddleware::requireAuth();
        RateLimitMiddleware::check('users_get', 60, 60);

        $controller = $GLOBALS['container']->get(UserController::class);
        $type       = $request->getQuery('type');

        return $controller->getAddresses($userId, $type);
    });

    // ---------------------------------------------------------------------
    // ADMIN / LIST / SEARCH
    // ---------------------------------------------------------------------

    // GET /api/v1/admin/users
    $router->get('/admin/users', function (Request $request): array {
        AuthMiddleware::requireAdmin();
        RateLimitMiddleware::check('users_get', 60, 60);

        $controller = $GLOBALS['container']->get(UserController::class);

        $limit  = (int)min(100, max(1, (int)($request->getQuery('limit', 50))));
        $offset = (int)max(0, (int)($request->getQuery('offset', 0)));

        return $controller->getAllUsers($limit, $offset);
    });

    // GET /api/v1/users (with optional search)
    $router->get('/users', function (Request $request): array {
        AuthMiddleware::requireAdmin(); // Admin only for user list
        RateLimitMiddleware::check('users_get', 60, 60);

        $controller = $GLOBALS['container']->get(UserController::class);

        $limit  = (int)$request->getQuery('limit', 20);
        $offset = (int)$request->getQuery('offset', 0);
        $search = trim((string)$request->getQuery('search', ''));

        // Use search method if query provided, otherwise getAll
        if ($search !== '') {
            return $controller->searchUsers($search, $limit, $offset);
        }
        return $controller->getAllUsers($limit, $offset);
    });

    // GET /api/v1/users/search
    $router->get('/users/search', function (Request $request): array {
        AuthMiddleware::requireAdmin(); 
        $controller = $GLOBALS['container']->get(UserController::class);

        $query  = (string)$request->getQuery('search', '');
        $limit  = (int)$request->getQuery('limit', 50);
        $offset = (int)$request->getQuery('offset', 0);

        return $controller->searchUsers($query, $limit, $offset);
    });

    // GET /api/v1/users/:id - Get single user by ID (admin enriched)
    $router->get('/users/:id', function (Request $request, array $params): array {
        AuthMiddleware::requireAdmin();
        RateLimitMiddleware::check('users_get', 60, 60);

        $controller = $GLOBALS['container']->get(UserController::class);
        $id = (int)($params['id'] ?? 0);

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'User ID required',
                'code'    => 400,
            ];
        }

        return $controller->getByIdEnriched($id);
    });

    // PUT /api/v1/users/:id - Update user (admin)
    $router->put('/users/:id', function (Request $request, array $params): array {
        AuthMiddleware::requireAdmin();
        RateLimitMiddleware::check('users_put', 30, 60);

        $controller = $GLOBALS['container']->get(UserController::class);
        $id = (int)($params['id'] ?? 0);

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'User ID required',
                'code'    => 400,
            ];
        }

        $body = $request->getAllBody();
        return $controller->updateProfile($id, $body);
    });

    // DELETE /api/v1/users/:id - Delete user (admin)
    $router->delete('/users/:id', function (Request $request, array $params): array {
        AuthMiddleware::requireAdmin();
        RateLimitMiddleware::check('users_delete', 20, 60);

        $controller = $GLOBALS['container']->get(UserController::class);
        $id = (int)($params['id'] ?? 0);

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'User ID required',
                'code'    => 400,
            ];
        }

        $hard = $request->getQuery('hard', 'false') === 'true';
        return $controller->delete($id, $hard);
    });
});
