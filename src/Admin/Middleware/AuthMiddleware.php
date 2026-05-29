<?php
declare(strict_types=1);

namespace App\Admin\Middleware;

use App\Core\Session;
use App\Interfaces\MiddlewareInterface;
use App\Core\Request;
use App\Admin\Exceptions\UnauthorizedException;
use App\Admin\Exceptions\ForbiddenException;

class AuthMiddleware implements MiddlewareInterface {

    private bool $requireAdmin;

    /**
     * Constructor
     *
     * @param bool $requireAdmin
     */
    public function __construct(bool $requireAdmin = false)
    {
        $this->requireAdmin = $requireAdmin;
    }

    /**
     * Handle the request and pass to the next middleware in the stack
     *
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function handle(Request $request, callable $next): mixed
    {
        if ($this->requireAdmin) {
            self::requireAdmin();
        } else {
            self::requireAuth();
        }
        return $next($request);
    }

    /**
     * Helper function to check authentication (legacy static fallback)
     * @return int User ID
     * @throws UnauthorizedException
     */
    public static function requireAuth(): int {
        $session = Session::getInstance();
        
        if (!$session->isLoggedIn()) {
            throw new UnauthorizedException("Unauthorized - Please login");
        }
        
        return (int)$session->get('user_id');
    }

    /**
     * Helper function to check admin role (legacy static fallback)
     * @return int User ID
     * @throws UnauthorizedException
     * @throws ForbiddenException
     */
    public static function requireAdmin(): int {
        $session = Session::getInstance();
        
        if (!$session->isLoggedIn()) {
            throw new UnauthorizedException("Unauthorized - Please login");
        }
        
        if (!$session->isAdmin()) {
            throw new ForbiddenException("Forbidden - Admin access required");
        }
        
        return (int)$session->get('user_id');
    }

    /**
     * Helper function to send JSON response and exit (legacy fallback)
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     */
    public static function sendResponse(array $data, int $statusCode = 200): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}
