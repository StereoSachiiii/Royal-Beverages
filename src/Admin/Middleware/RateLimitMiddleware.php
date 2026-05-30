<?php
declare(strict_types=1);

namespace App\Admin\Middleware;

use App\Core\Session;
use App\Interfaces\MiddlewareInterface;
use App\Core\Request;
use App\Admin\Exceptions\RateLimitException;

class RateLimitMiddleware implements MiddlewareInterface
{
    private static ?Session $session = null;
    private static ?\Redis $redis = null;

    // Defaults — can be changed at runtime via configure()
    private static int $maxRequests = 3;
    private static int $timeWindow = 60; // seconds
    private static string $baseKey = 'rate_limit';

    private string $key;
    private int $maxRequestsInstance;
    private int $timeWindowInstance;

    /**
     * Constructor
     *
     * @param string $key Unique rate-limit prefix
     * @param int $maxRequests Max permitted requests in window
     * @param int $timeWindow Window length in seconds
     */
    public function __construct(string $key, int $maxRequests = 3, int $timeWindow = 60)
    {
        $this->key = $key;
        $this->maxRequestsInstance = $maxRequests;
        $this->timeWindowInstance = $timeWindow;
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
        self::check($this->key, $this->maxRequestsInstance, $this->timeWindowInstance);
        return $next($request);
    }

    /**
     * static check method (legacy fallback)
     *
     * @throws RateLimitException
     */
    public static function check(string $key, int $maxRequests = 3, int $timeWindow = 60): void
    {
        self::configure($maxRequests, $timeWindow);
        if (self::checkExecute($key)) {
            throw new RateLimitException();
        }
    }

    public static function configure(int $maxRequests, int $timeWindowSeconds): void
    {
        if ($maxRequests > 0) {
            self::$maxRequests = $maxRequests;
        }
        if ($timeWindowSeconds > 0) {
            self::$timeWindow = $timeWindowSeconds;
        }
    }

    /**
     * Check rate limit for the current session or a provided identifier.
     */
    public static function checkExecute(?string $identifier = null): bool
    {
        $key = self::makeKey($identifier);
        $now = time();

        if (extension_loaded('redis')) {
            if (self::$redis === null) {
                try {
                    self::$redis = new \Redis();
                    // Connect using env vars if available, else fallback to 127.0.0.1
                    $host = getenv('REDIS_HOST') ?: '127.0.0.1';
                    $port = (int)(getenv('REDIS_PORT') ?: 6379);
                    self::$redis->connect($host, $port, 1.0); // 1s timeout
                } catch (\Exception $e) {
                    // Fallback to session if redis connection fails
                    self::$redis = false;
                }
            }
            
            if (self::$redis) {
                return self::checkExecuteRedis($key, $now);
            }
        }

        return self::checkExecuteSession($key, $now);
    }

    private static function checkExecuteRedis(string $key, int $now): bool
    {
        try {
            $count = self::$redis->get($key);
            if ($count === false) {
                self::$redis->set($key, 1, self::$timeWindow);
                return false;
            }

            if ((int)$count >= self::$maxRequests) {
                return true; // Exceeded
            }

            self::$redis->incr($key);
            return false;
        } catch (\Exception $e) {
            return false; // Fail open to avoid breaking requests
        }
    }

    private static function checkExecuteSession(string $key, int $now): bool
    {
        self::$session = Session::getInstance();

        // Initialize if missing
        if (!self::$session->has($key)) {
            self::$session->set($key, [
                'count'      => 1,
                'start_time' => $now,
            ]);
            return false;
        }

        $rateData = self::$session->get($key);

        $count = isset($rateData['count']) ? (int)$rateData['count'] : 0;
        $start = isset($rateData['start_time']) ? (int)$rateData['start_time'] : $now;

        $elapsed = $now - $start;

        if ($elapsed < self::$timeWindow) {
            if ($count >= self::$maxRequests) {
                return true; // exceeded
            }

            $rateData['count'] = $count + 1;
            $rateData['start_time'] = $start;
            self::$session->set($key, $rateData);
            return false;
        }

        // Reset
        self::$session->set($key, [
            'count'      => 1,
            'start_time' => $now,
        ]);
        return false;
    }

    public static function reset(?string $identifier = null): void
    {
        self::$session = Session::getInstance();
        $key = self::makeKey($identifier);
        if (self::$session->has($key)) {
            self::$session->remove($key);
        }
    }

    private static function makeKey(?string $identifier = null): string
    {
        if ($identifier !== null && $identifier !== '') {
            return self::$baseKey . ':' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $identifier);
        }

        return self::$baseKey;
    }

    public static function emitLimitExceededResponse(?string $message = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => $message ?? 'Rate limit exceeded. Try again later.',
            'data'    => null,
            'code'    => 429,
            'context' => []
        ]);
        exit;
    }
}
