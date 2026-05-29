<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Core\Session;
use App\Core\Request;
use App\Core\Router;
use App\Admin\Middleware\AuthMiddleware;
use App\Admin\Middleware\CSRFMiddleware;
use App\Admin\Middleware\RateLimitMiddleware;
use App\Admin\Exceptions\UnauthorizedException;
use App\Admin\Exceptions\ForbiddenException;
use App\Admin\Exceptions\RateLimitException;

class MiddlewareTest extends TestCase
{
    private Session $session;

    protected function setUp(): void
    {
        // Ensure session has clean array
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $this->session = Session::getInstance();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    // ------------------------------------------------------------------------
    // AuthMiddleware Tests
    // ------------------------------------------------------------------------

    public function testAuthMiddlewareAllowsLoggedInUser(): void
    {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = 123;

        $middleware = new AuthMiddleware(false);
        $request = Request::createFromGlobals();

        $nextCalled = false;
        $next = function (Request $req) use (&$nextCalled) {
            $nextCalled = true;
            return 'passed';
        };

        $result = $middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertEquals('passed', $result);
    }

    public function testAuthMiddlewareThrowsUnauthorizedIfNotLoggedIn(): void
    {
        $this->expectException(UnauthorizedException::class);

        $middleware = new AuthMiddleware(false);
        $request = Request::createFromGlobals();
        $next = function () { return 'passed'; };

        $middleware->handle($request, $next);
    }

    public function testAuthMiddlewareThrowsForbiddenIfNotAdminWhenRequired(): void
    {
        $_SESSION['logged_in'] = true;
        $_SESSION['is_admin'] = false;

        $this->expectException(ForbiddenException::class);

        $middleware = new AuthMiddleware(true);
        $request = Request::createFromGlobals();
        $next = function () { return 'passed'; };

        $middleware->handle($request, $next);
    }

    public function testAuthMiddlewareAllowsAdminWhenRequired(): void
    {
        $_SESSION['logged_in'] = true;
        $_SESSION['is_admin'] = true;

        $middleware = new AuthMiddleware(true);
        $request = Request::createFromGlobals();

        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return 'passed';
        };

        $result = $middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertEquals('passed', $result);
    }

    // ------------------------------------------------------------------------
    // CSRFMiddleware Tests
    // ------------------------------------------------------------------------

    public function testCsrfMiddlewareAllowsValidToken(): void
    {
        $_SESSION['csrf_token'] = 'securetoken123';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'securetoken123';

        $middleware = new CSRFMiddleware();
        $request = Request::createFromGlobals();

        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return 'passed';
        };

        $result = $middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertEquals('passed', $result);

        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }

    public function testCsrfMiddlewareThrowsForbiddenIfTokenMissing(): void
    {
        $_SESSION['csrf_token'] = 'securetoken123';
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        }
        if (isset($_POST['csrf_token'])) {
            unset($_POST['csrf_token']);
        }

        $this->expectException(ForbiddenException::class);

        $middleware = new CSRFMiddleware();
        $request = Request::createFromGlobals();
        $next = function () { return 'passed'; };

        $middleware->handle($request, $next);
    }

    // ------------------------------------------------------------------------
    // RateLimitMiddleware Tests
    // ------------------------------------------------------------------------

    public function testRateLimitMiddlewareAllowsWithinBounds(): void
    {
        // 2 requests allowed in 60s
        $middleware = new RateLimitMiddleware('test_limit', 2, 60);
        $request = Request::createFromGlobals();

        $next = function () { return 'passed'; };

        $res1 = $middleware->handle($request, $next);
        $res2 = $middleware->handle($request, $next);

        $this->assertEquals('passed', $res1);
        $this->assertEquals('passed', $res2);
    }

    public function testRateLimitMiddlewareThrowsRateLimitExceptionIfExceeded(): void
    {
        // 1 request allowed in 60s
        $middleware = new RateLimitMiddleware('test_exceeded_limit', 1, 60);
        $request = Request::createFromGlobals();

        $next = function () { return 'passed'; };

        // First request is OK
        $middleware->handle($request, $next);

        // Second request throws exception
        $this->expectException(RateLimitException::class);
        $middleware->handle($request, $next);
    }

    // ------------------------------------------------------------------------
    // Router Middleware Pipeline Tests
    // ------------------------------------------------------------------------

    public function testRouterExecutesMiddlewaresAndFinalHandler(): void
    {
        $router = new Router();
        
        $executionOrder = [];

        $mw1 = new class($executionOrder) implements \App\Interfaces\MiddlewareInterface {
            private array $order;
            public function __construct(array &$order) { $this->order = &$order; }
            public function handle(Request $req, callable $next): mixed {
                $this->order[] = 'mw1_start';
                $res = $next($req);
                $this->order[] = 'mw1_end';
                return $res;
            }
        };

        $mw2 = new class($executionOrder) implements \App\Interfaces\MiddlewareInterface {
            private array $order;
            public function __construct(array &$order) { $this->order = &$order; }
            public function handle(Request $req, callable $next): mixed {
                $this->order[] = 'mw2_start';
                $res = $next($req);
                $this->order[] = 'mw2_end';
                return $res;
            }
        };

        $router->get('/test-pipeline', function () use (&$executionOrder) {
            $executionOrder[] = 'handler';
            return 'handler_response';
        })->middleware([$mw1, $mw2]);

        // Mock a GET /test-pipeline request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test-pipeline';
        $request = Request::createFromGlobals();

        $response = $router->dispatch($request);

        $this->assertEquals('handler_response', $response);
        $this->assertEquals([
            'mw1_start',
            'mw2_start',
            'handler',
            'mw2_end',
            'mw1_end'
        ], $executionOrder);
    }
}
