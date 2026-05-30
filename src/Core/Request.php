<?php
declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Request Object
 * 
 * Encapsulates HTTP request data and provides convenient access methods
 */
class Request
{
    private string $method;
    private string $uri;
    private array $headers;
    private array $query;
    private array $body;
    private array $route;
    private array $server;
    private array $files;
    private ?object $user = null;

    public function __construct(
        string $method,
        string $uri,
        array $headers = [],
        array $query = [],
        array $body = [],
        array $server = [],
        array $files = []
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->headers = $headers;
        $this->query = $query;
        $this->body = $body;
        $this->route = [];
        $this->server = $server;
        $this->files = $files;
    }

    /**
     * Create Request from PHP globals
     *
     * @return self
     */
    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $pathInfo   = $_SERVER['PATH_INFO'] ?? '';

        // Extract path from REQUEST_URI (strip query string)
        $parsedUri = parse_url($requestUri, PHP_URL_PATH);
        $uri = is_string($parsedUri) ? $parsedUri : '/';

        // 1. Prioritize PATH_INFO if available (the cleanest way)
        if (!empty($pathInfo)) {
            $uri = $pathInfo;
        } else {
            // 2. Otherwise, strip the WEB_ROOT if it's there
            if (defined('WEB_ROOT')) {
                $base = rtrim(WEB_ROOT, '/') . '/';
                if ($base !== '/' && str_starts_with($uri, $base)) {
                    $uri = substr($uri, strlen($base) - 1); // Keep the leading slash
                }
            }
            
            // 3. Special case for legacy API entry point (only if not using modern /api/v1)
            $apiBase = '/admin/api';
            if (str_starts_with($uri, $apiBase) && !str_contains($uri, '/api/v1')) {
                $uri = substr($uri, strlen($apiBase));
            }
        }

        // 4. Ensure a clean format for the Router: single leading slash, no trailing slash
        $uri = '/' . ltrim($uri, '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        // Parse headers
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'HTTP_')) {
                $headerKey = str_replace('_', '-', substr($key, 5));
                $headers[$headerKey] = $value;
            }
        }
        
        // Add content type if present
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }

        // Parse body based on content type
        $body = [];
        $contentType = is_string($headers['Content-Type'] ?? null) ? $headers['Content-Type'] : '';
        
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            if (str_contains($contentType, 'application/json')) {
                $rawBody = file_get_contents('php://input');
                if ($rawBody === false) {
                    $rawBody = '';
                }
                $body = json_decode($rawBody, true);
                if (!is_array($body)) {
                    $body = [];
                }
            } else {
                $body = $_POST;
            }
        }

        return new self(
            $method,
            $uri,
            $headers,
            $_GET,
            $body,
            $_SERVER,
            $_FILES
        );
    }

    /**
     * Get HTTP method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get request URI (Alias for getUri)
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->uri;
    }

    /**
     * Get request URI
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get a header value
     *
     * @param string $name
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        // Case-insensitive header lookup
        $name = strtoupper(str_replace('-', '_', $name));
        
        foreach ($this->headers as $key => $value) {
            if (strtoupper(str_replace('-', '_', $key)) === $name) {
                return $value;
            }
        }
        
        return null;
    }

    /**
     * Get all headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get a query parameter
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get all query parameters
     *
     * @return array
     */
    public function getAllQuery(): array
    {
        return $this->query;
    }

    /**
     * Get a body parameter
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getBody(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * Get all body parameters
     *
     * @return array
     */
    public function getAllBody(): array
    {
        return $this->body;
    }

    /**
     * Get a route parameter
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getRouteParam(string $key, mixed $default = null): mixed
    {
        return $this->route[$key] ?? $default;
    }

    /**
     * Set route parameters (used by router)
     *
     * @param array $params
     * @return void
     */
    public function setRouteParams(array $params): void
    {
        $this->route = $params;
    }

    /**
     * Get all route parameters
     *
     * @return array
     */
    public function getRouteParams(): array
    {
        return $this->route;
    }

    /**
     * Get all parameters (query + body + route)
     *
     * @return array
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body, $this->route);
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    public function ip(): string
    {
        $headers = [
            'REMOTE_ADDR',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
        ];

        foreach ($headers as $header) {
            if (isset($this->server[$header])) {
                $ip = $this->server[$header];
                // Handle comma-separated list of IPs
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Check if request is JSON
     *
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->getHeader('Content-Type') ?? '';
        return str_contains($contentType, 'application/json');
    }

    /**
     * Get authenticated user (set by authentication middleware)
     *
     * @return object|null
     */
    public function getUser(): ?object
    {
        return $this->user;
    }

    /**
     * Set authenticated user (used by authentication middleware)
     *
     * @param object $user
     * @return void
     */
    public function setUser(object $user): void
    {
        $this->user = $user;
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    /**
     * Get uploaded file
     *
     * @param string $key
     * @return array|null
     */
    public function getFile(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get all uploaded files
     *
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Check if file was uploaded
     *
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }
}
