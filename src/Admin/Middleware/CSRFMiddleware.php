<?php
declare(strict_types=1);

namespace App\Admin\Middleware;

use App\Core\Session;
use App\Interfaces\MiddlewareInterface;
use App\Core\Request;
use App\Admin\Exceptions\ForbiddenException;

class CSRFMiddleware implements MiddlewareInterface {

    /**
     * Handle the request and pass to the next middleware in the stack
     *
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function handle(Request $request, callable $next): mixed
    {
        self::verifyCsrf();
        return $next($request);
    }

    /**
     * Verify CSRF token for state-changing operations
     *
     * @throws ForbiddenException
     */
    public static function verifyCsrf(): void {
        $session = Session::getInstance();
        $csrf = $session->getCsrfInstance();
        
        // Get token from header or POST data
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
        
        if (!$token || !$csrf->validateToken($token)) {
            throw new ForbiddenException("Invalid CSRF token");
        }
    }
}
