# Royal Beverages — Architecture

No frameworks. No Laravel. No React. No jQuery. Vanilla PHP 8.2 and ES6 JavaScript.
Built from scratch to understand how web systems  work.

---

## Why This Exists

Every pattern here  MVC, DI, routing, middleware  is hand-rolled.
The Reflection API is used for auto-wiring.
---

## Core Engine (`src/Core/`)

### Autoloader (`src/Core/Autoloader.php`)

Custom PSR-4 autoloader.
```php
// src/Core/Autoloader.php
class Autoloader
{
    private array $prefixes = [];
    private array $globalDirs = [];

    public function addNamespace(string $prefix, string $baseDir): void
    {
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $prefix = trim($prefix, '\\');

        if ($prefix === '') {
            $this->globalDirs[] = $baseDir;
            return;
        }

        $prefix .= '\\';
        $this->prefixes[$prefix][] = $baseDir;
    }

    public function loadClass(string $class): bool
    {
        $class = ltrim($class, '\\');

        // Try namespaced prefixes
        foreach ($this->prefixes as $prefix => $baseDirs) {
            if (str_starts_with($class, $prefix)) {
                $relativeClass = substr($class, strlen($prefix));
                foreach ($baseDirs as $baseDir) {
                    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
                    if (file_exists($file)) {
                        require $file;
                        return true;
                    }
                }
            }
        }

        //  Fallback: scan global dirs for non-namespaced classes
        if (!str_contains($class, '\\')) {
            foreach ($this->globalDirs as $baseDir) {
                $file = $baseDir . $class . '.php';
                if (file_exists($file)) {
                    require $file;
                    return true;
                }
            }
        }

        return false;
    }
}
```

Two resolution strategies:
- **Namespaced**: Maps `App\` to `src/`. Standard PSR-4.
- **Global fallback**: Scans legacy directories for non-namespaced classes like `Session` or `Database`.

### Bootstrap (`src/Core/bootstrap.php`)

Single entry point that wires up the runtime. Every page (public or admin) includes this.

```php
// 6. Detect BASE_URL, WEB_ROOT, API_BASE_URL dynamically

```

### Database — Singleton (`src/Core/Database.php`)

```php
class Database {
    private static ?PDO $pdo = null;

    public static function getPdo(): PDO {
        if (self::$pdo === null) {
            $config = $GLOBALS['app_config'];
            $dsn = "pgsql:host={$host};port={$port};dbname={$db};";
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }
}
```

One connection per request. Lazy-loaded. Any class can call `Database::getPdo()`.

### Session — Singleton (`src/Core/Session.php`)

```php
class Session {
    private static ?Session $instance = null;

    private function __construct($timeout = 86400) {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Inactivity timeout — destroy and re-init as guest
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $this->timeout) {
            $this->destroy();
            $this->initGuest();
        }
        $_SESSION['last_activity'] = time();

        // If no user, init as guest with random ID
        if (!isset($_SESSION['user_id'])) $this->initGuest();

        // CSRF token generated on construction
        $this->csrf = new CSRF($this);
        $this->csrf->getToken();
    }
}
```

Features:
- 24-hour inactivity timeout
- Automatic guest user initialization (`guest_` + `bin2hex(random_bytes(16))`)
- CSRF token created on session start
- `session_regenerate_id(true)` on login to prevent fixation

### CSRF (`src/Core/CSRF.php`)

```php
class CSRF {
    public function generateToken(): string {
        $token = bin2hex(random_bytes(32));
        $this->session->set('csrf_token', $token);
        return $token;
    }

    public function validateToken(string $token): bool {
        $currentCSRFToken = $this->getToken();
        return $currentCSRFToken ? hash_equals($currentCSRFToken, $token) : false;
    }
}
```

`hash_equals` prevents timing attacks. Token is injected into HTML via `<meta>` tag, read by JavaScript on every mutating request.

---

## Routing

### Backend Router (`src/Core/Router.php`)

Regex-based. Converts `:param` syntax to named capture groups.

```php
class Router
{
    private function addRoute(string $method, string $path, callable $handler): void
    {
        $fullPath = $this->normalizePath($this->currentGroupPrefix, $path);

        $paramNames = [];
        $pattern = preg_replace_callback('#:([a-zA-Z_][a-zA-Z0-9_]*)#', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $fullPath);

        $regex = '#^' . $pattern . '$#';

        $this->routes[$method][] = [
            'pattern'    => $regex,
            'paramNames' => $paramNames,
            'handler'    => $handler,
        ];
    }

    public function dispatch(Request $request): mixed
    {
        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named params, call handler
                return $handler($request, $params);
            }
        }
        return null; // 404
    }
}
```

Route registration uses grouping:

```php
$router->group('/api/v1', function($r) {
    $r->get('/products', fn($req) => $productController->getAll($req));
    $r->get('/products/:id', fn($req, $p) => $productController->getById((int)$p['id']));
    $r->post('/products', fn($req) => $productController->create($req));
    $r->put('/products/:id', fn($req, $p) => $productController->update((int)$p['id'], $req));
    $r->delete('/products/:id', fn($req, $p) => $productController->delete((int)$p['id']));
});
```

### API Gateway — URI Normalization (`public/api/v1/index.php`)

The gateway sits 3 directories deep. Apache's `.htaccess` rewrites everything under `/api/v1/` to `index.php`, but the `Request` object ends up with a URI relative to the file, not the route definition. So the URI has to be normalized using Reflection:

```php
$uri = $request->getUri();
if (!str_starts_with($uri, '/api/v1')) {
    $cleanUri = '/api/v1/' . ltrim($uri, '/');

    // Private property — Reflection is the only way in
    $ref = new ReflectionClass($request);
    $prop = $ref->getProperty('uri');
    $prop->setAccessible(true);
    $prop->setValue($request, $cleanUri);
}
```

---

## Dependency Injection Container (`src/DIContainer/Container.php`)

Auto-wiring via PHP Reflection. No config files. It reads constructor signatures and recursively resolves dependencies.

```php
class Container
{
    private array $bindings = [];
    private array $instances = [];
    private array $resolving = []; // circular dependency detection

    public function get(string $id)
    {
        if (isset($this->instances[$id])) return $this->instances[$id];

        // Circular dependency check
        if (isset($this->resolving[$id])) {
            throw new ContainerException("Circular dependency: " . implode(' -> ', array_keys($this->resolving)));
        }

        $this->resolving[$id] = true;

        try {
            $concrete = $this->bindings[$id]['concrete'] ?? $id;
            $object = ($concrete instanceof Closure) ? $concrete($this) : $this->resolve($concrete);

            if (isset($this->bindings[$id]) && $this->bindings[$id]['singleton']) {
                $this->instances[$id] = $object;
            }
            return $object;
        } finally {
            unset($this->resolving[$id]);
        }
    }

    private function resolve(string $class): object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if (!$constructor || empty($constructor->getParameters())) {
            return new $class();
        }

        // Recursively resolve each constructor parameter
        $dependencies = array_map(function (ReflectionParameter $param) use ($class) {
            $type = $param->getType();

            if (!$type || !($type instanceof ReflectionNamedType) || $type->isBuiltin()) {
                if ($param->isDefaultValueAvailable()) return $param->getDefaultValue();
                throw new ContainerException("Cannot resolve '{$param->getName()}' in '{$class}'");
            }

            return $this->get($type->getName());
        }, $constructor->getParameters());

        return $reflection->newInstanceArgs($dependencies);
    }
}
```

When you call `$container->get(ProductController::class)`, it:
1. Reflects `ProductController` constructor — sees it needs `ProductService`
2. Reflects `ProductService` constructor — sees it needs `ProductRepository`
3. Reflects `ProductRepository` — no typed deps, instantiates it
4. Builds up the chain: Repository → Service → Controller

### Service Provider (`src/Admin/API/ApiServiceProvider.php`)

Registers everything as singletons in bulk:

```php
class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->instance(Session::class, Session::getInstance());

        foreach ($this->repositories() as $class) $this->container->singleton($class);
        foreach ($this->services() as $class) $this->container->singleton($class);
        foreach ($this->controllers() as $class) $this->container->singleton($class);
    }
}
```

Current counts: 20 Repositories, 21 Services, 22 Controllers, 18 Models.

---

## MVC-S-R Architecture

Strict 4-layer separation. No business logic in controllers. No SQL in services.

```
Request
  → Router (regex match)
    → Controller (parse input, delegate)
      → Service (business logic, validation)
        → Repository (SQL, returns Models)
          → PostgreSQL
```

### BaseController

Every controller extends this. `handle()` wraps all actions with exception handling:

```php
abstract class BaseController
{
    protected function handle(callable $callback): array
    {
        try {
            return $callback();
        } catch (BaseException $e) {
            throw $e; // domain exceptions bubble up
        } catch (Throwable $e) {
            throw new DatabaseException('Unexpected error: ' . $e->getMessage());
        }
    }

    protected function success(string $message, mixed $data = null, int $code = 200): array
    {
        return ['success' => true, 'message' => $message, 'data' => $data, 'code' => $code];
    }
}
```

### Models — PHP 8.2 Constructor Promotion

```php
class ProductModel
{
    public function __construct(
        private ?int $id = null,
        private ?string $name = null,
        private ?string $slug = null,
        private ?int $price_cents = null,     // money in cents, no floats
        private ?int $category_id = null,
        private bool $is_active = true,
        private ?string $deleted_at = null     // soft delete
    ) {}

    public function toArray(): array { /* ... */ }
}
```

All money is stored in cents. `$5,000.00 → 500000`. No floating point errors.

### Exception Hierarchy

```
BaseException (abstract)
├── DatabaseException        (500)
├── NotFoundException        (404)
├── ValidationException      (400 + field errors)
├── UnauthorizedException    (401)
├── DuplicateException       (409)
└── DuplicateEmailException  (409)
```

Each carries a `$context` array and a `$statusCode`. The API gateway catches them and formats the JSON response.

---

## Middleware Pipeline (`src/Core/MiddlewareStack.php`)

Chain of Responsibility pattern:

```php
class MiddlewareStack
{
    public function process(Request $request, callable $finalHandler): Response
    {
        $next = $this->createNext($this->middleware, $finalHandler);
        return $next($request);
    }

    private function createNext(array $middleware, callable $finalHandler): callable
    {
        if (empty($middleware)) return $finalHandler;

        $current = array_shift($middleware);
        return fn(Request $request) => $current->handle(
            $request,
            $this->createNext($middleware, $finalHandler)
        );
    }
}
```

Middleware stack:

| Middleware | Purpose |
|---|---|
| `AuthMiddleware` | Validates session, checks admin privileges |
| `CSRFMiddleware` | Token validation on POST/PUT/DELETE |
| `JsonMiddleware` | Sets `Content-Type: application/json` |
| `CorsMiddleware` | CORS headers |
| `RateLimitMiddleware` | Per-session sliding window throttling |

### Rate Limiting

Session-based sliding window. No Redis needed.

```php
class RateLimitMiddleware {
    private static int $maxRequests = 3;
    private static int $timeWindow = 60; // seconds

    public static function check(string $key): void {
        $session = Session::getInstance();
        $rateData = $session->get("rate_limit:{$key}");
        $elapsed = time() - ($rateData['start_time'] ?? time());

        if ($elapsed < self::$timeWindow && $rateData['count'] >= self::$maxRequests) {
            // 429 Too Many Requests
        }
    }
}
```

---

## Response Contract (`src/Core/Response.php`)

Every API response follows the same shape:

```php
class Response
{
    public static function success(string $message, mixed $data = null, int $code = 200): self;
    public static function error(string $message, array $errors = [], int $code = 400): self;
    public static function paginated(array $items, int $total, int $limit, int $offset): self;
    public static function notFound(string $message): self;      // 404
    public static function unauthorized(string $message): self;  // 401
    public static function forbidden(string $message): self;     // 403
    public static function validationError(string $message, array $errors): self; // 422
    public static function conflict(string $message): self;      // 409
    public static function tooManyRequests(string $message): self; // 429
    public static function serverError(string $message): self;   // 500
    public static function created(string $message, mixed $data): self; // 201
    public static function noContent(): self;                    // 204
}
```

Standard JSON output:

```json
{
    "success": true,
    "message": "Products retrieved",
    "data": [...],
    "meta": { "total": 42, "limit": 20, "offset": 0, "count": 20 }
}
```

---

## Frontend API Client (`public/assets/js/api-helper.js`)

Single module for all frontend-to-backend communication.

```javascript
export async function apiRequest(endpoint, options = {}) {
    const url = API_BASE_URL + endpoint;
    const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };

    // Auto-inject CSRF token on mutating requests
    if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(options.method?.toUpperCase())) {
        headers['X-CSRF-Token'] = getCsrfToken();
    }

    const response = await fetch(url, {
        method: options.method || 'GET',
        headers,
        credentials: 'include',
        body: options.body ? JSON.stringify(options.body) : undefined
    });

    const data = await response.json();
    if (!data.success) throw new Error(data.message);
    return data;
}
```

Domain-organized API object:

```javascript
export const API = {
    products: {
        list: (params) => apiRequest('/products' + buildQuery(params)),
        get: (id) => apiRequest('/products/' + id),
        search: (query) => apiRequest('/products/search' + buildQuery({ search: query })),
    },
    cart: {
        addItem: (data) => apiRequest('/cart-items', { method: 'POST', body: data }),
        removeItem: (id) => apiRequest('/cart-items/' + id, { method: 'DELETE' }),
    },
    orders: { /* ... */ },
    users: { /* login, register, logout, getCurrentUser */ },
    addresses: { /* CRUD + setDefault */ },
    wishlist: { /* get, add, remove, sync */ },
    recipes: { /* list, search, getIngredients */ },
    // 10+ domain modules total
};
```

---

## Admin SPA (`public/admin/js/`)

The admin panel is a framework-less Single Page Application.

### Route Registry (`render.js`)

```javascript
export const ROUTE_MAP = {
    'products': {
        module: () => import('./pages/Products/Products.js'),
        view: 'Products',
        init: 'initProducts'
    },
    'orders': {
        module: () => import('./pages/Orders/Orders.js'),
        view: 'Orders',
        init: 'initOrders'
    },
    // 18+ pages
};
```

### How It Works

1. Single HTML shell: `admin/index.php` loads once with sidebar, header, and empty `<main id="content">`
2. Hash-based navigation: `#products`, `#orders` trigger page swaps
3. `render()` dynamically imports the page module, calls its exported view function for HTML, then calls `init()` which returns a `{ cleanup() }` object
4. Before each transition, `cleanupActiveHandler()` runs the previous page's cleanup to remove event listeners and prevent  leaks
5. Last visited page is persisted to `localStorage`

### EntityBuilder Pattern

To prevent duplicate code across the 18+ CRUD admin pages, we use a central `EntityBuilder` pattern. Instead of hardcoding tables and forms for each entity, the frontend defines a declarative configuration for columns and fields, and the `EntityBuilder` automatically constructs the List Views, Data Tables, and Add/Edit Modals. This architectural shift greatly consolidated the admin pages.

### State Persistence (`admin/js/utils.js`)

```javascript
export function saveState(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
}
export function getState(key, defaultValue) {
    const stored = localStorage.getItem(key);
    return stored ? JSON.parse(stored) : defaultValue;
}
```

Used for: cart state, wishlist, user preferences, admin page state.

---

## Database (PostgreSQL)

### Connection Pooling

To handle high traffic and prevent PHP from exhausting PostgreSQL connections (since PHP normally opens a new connection per request without persistent pooling), we use **PgBouncer** as a lightweight middleware. 
- **Mode:** Transaction pooling
- **Authentication:** `scram-sha-256` (matching Postgres 15 defaults)
- The PHP app connects to PgBouncer on port `6432`, which multiplexes queries onto a small pool of persistent database connections.

### Schema Design

17 tables. Enums for status fields. Foreign keys everywhere.

| Table | Purpose |
|---|---|
| `users` | Accounts, admin flag, OAuth fields, soft delete, GDPR anonymization |
| `products` | Catalog. `price_cents` integer. Soft delete. |
| `categories` | Product grouping with slugs |
| `stock` | Per-warehouse inventory. `quantity` and `reserved` columns. |
| `warehouses` | Physical locations |
| `suppliers` | Product sources |
| `carts` | Session-based. Status enum: active/converted/abandoned/expired |
| `cart_items` | `price_at_add_cents` preserves the price at time of add |
| `orders` | Full lifecycle timestamps: `paid_at`, `shipped_at`, `delivered_at`, `cancelled_at` |
| `order_items` | Snapshot of product at purchase time. `warehouse_id` for fulfillment tracking. |
| `payments` | Gateway-agnostic. JSONB payload for raw gateway response. |
| `feedback` | Ratings 1-5, verified purchase flag |
| `flavor_profiles` | 6-axis taste profile (sweetness, bitterness, strength, smokiness, fruitiness, spiciness) |
| `cocktail_recipes` | Recipes with difficulty, prep time, servings |
| `recipe_ingredients` | Links recipes to products with quantities |
| `user_preferences` | Mirror of flavor_profiles but for user taste preferences |
| `product_recognition` | AI image recognition results (WIP) |

### Enums

```sql
CREATE TYPE cart_status    AS ENUM ('active', 'converted', 'abandoned', 'expired');
CREATE TYPE order_status   AS ENUM ('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded', 'failed');
CREATE TYPE payment_status AS ENUM ('pending', 'captured', 'failed', 'refunded', 'voided');
```

### Indexing Strategy

```sql
-- Partial indexes (only index active, non-deleted rows)
CREATE INDEX idx_users_email_active ON users(email) WHERE is_active = TRUE AND deleted_at IS NULL;
CREATE INDEX idx_products_active ON products(id) WHERE is_active = TRUE AND deleted_at IS NULL;

-- Composite indexes for common queries
CREATE INDEX idx_orders_user_date ON orders(user_id, created_at DESC);
CREATE INDEX idx_orders_status_date ON orders(status, created_at DESC);

-- Unique partial indexes (only one active cart per user/session)
CREATE UNIQUE INDEX idx_carts_active_user ON carts(user_id) WHERE status = 'active';
CREATE UNIQUE INDEX idx_carts_active_session ON carts(session_id) WHERE status = 'active';

-- Low stock alerting
CREATE INDEX idx_stock_low ON stock(quantity ASC) WHERE quantity < 50;

-- Flavor profile indexes for recommendation queries
CREATE INDEX idx_flavor_sweetness ON flavor_profiles(sweetness);
CREATE INDEX idx_flavor_strength ON flavor_profiles(strength);
```

### Denormalized Admin Views

36 PostgreSQL views. Every entity has two:
- `admin_list_*` — lightweight columns for table rendering
- `admin_detail_*` — rich denormalized data with JSON aggregates for detail modals

Example:

```sql
CREATE VIEW admin_list_products AS
SELECT
    p.id, p.name, p.slug, p.price_cents,
    cat.name as category_name, sup.name as supplier_name,
    p.is_active, p.created_at,
    (SELECT COALESCE(SUM(s.quantity - s.reserved), 0) FROM stock s WHERE s.product_id = p.id) as available_stock,
    (SELECT AVG(rating) FROM feedback f WHERE f.product_id = p.id AND f.is_active = TRUE) as avg_rating
FROM products p
LEFT JOIN categories cat ON p.category_id = cat.id
LEFT JOIN suppliers sup ON p.supplier_id = sup.id
WHERE p.deleted_at IS NULL;
```

Detail views embed related data as JSON:

```sql
-- Stock by warehouse as JSON array
(SELECT JSON_AGG(row_to_json(t)) FROM (
    SELECT w.name as warehouse_name, s.quantity, s.reserved
    FROM stock s JOIN warehouses w ON s.warehouse_id = w.id
    WHERE s.product_id = p.id
) t) as stock_by_warehouse
```

### Soft Deletes

All major entities have `deleted_at TIMESTAMPTZ`. Orders track full lifecycle:

```sql
CREATE TABLE orders (
    created_at    TIMESTAMPTZ DEFAULT NOW(),
    updated_at    TIMESTAMPTZ DEFAULT NOW(),
    paid_at       TIMESTAMPTZ,
    shipped_at    TIMESTAMPTZ,
    delivered_at  TIMESTAMPTZ,
    cancelled_at  TIMESTAMPTZ
);
```

---

## Stock Management

Multi-warehouse FIFO stock deduction with row-level locking.

```php
// StockRepository.php — reserveStock()
public function reserveStock(int $orderId): void
{
    $this->pdo->beginTransaction();

    foreach ($orderItems as $item) {
        // Find warehouse with most available stock
        $stockStmt = $this->pdo->prepare("
            SELECT id, warehouse_id FROM stock
            WHERE product_id = :product_id
            AND (quantity - reserved) >= :quantity
            ORDER BY (quantity - reserved) DESC
            LIMIT 1
            FOR UPDATE  -- row lock, prevents race conditions
        ");

        // Reserve
        "UPDATE stock SET reserved = reserved + :quantity WHERE id = :stock_id";

        // Track which warehouse fulfills this item
        "UPDATE order_items SET warehouse_id = :warehouse_id WHERE order_id = :order_id AND product_id = :product_id";
    }

    $this->pdo->commit();
}
```

Stock lifecycle:

```
Order Created → reserveStock()     → reserved++
Payment OK    → confirmPayment()   → quantity -= reserved, reserved -= reserved
Cancelled     → cancelOrder()      → reserved-- (release back)
Refunded      → refundOrder()      → quantity++ (restock)
```

Available stock is always calculated: `quantity - reserved`. Never stored as a separate column.

---

## Security

| Feature | Implementation |
|---|---|
| CSRF | `hash_equals` timing-safe validation, token per session, auto-injected by JS on POST/PUT/DELETE |
| Passwords | `password_hash()` with BCrypt |
| Session fixation | `session_regenerate_id(true)` on login |
| SQL injection | Prepared statements everywhere. No string concatenation in queries. |
| XSS | Output escaping, CSP headers |
| Session timeout | 24-hour inactivity window |

---

## Quality Assurance & Testing

Strict CI pipeline enforced via `run_ci.ps1` (local) and GitHub Actions.

### Static Analysis & Linting

| Tool | Purpose | Configuration |
|---|---|---|
| **PHPStan** | Strict type analysis | Level 8 (`phpstan.neon`) + tracked baseline |
| **ESLint** | JavaScript logic checks | Flat config (`eslint.config.mjs`) |
| **Prettier** | Code formatting | Enforced across all `.js` and `.css` files |

### Automated Tests (PHPUnit)

Comprehensive unit tests (`tests/Unit/`) with strict coverage thresholds.

```xml
<!-- phpunit.xml -->
<phpunit bootstrap="tests/bootstrap.php" colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
    <source>
        <include><directory>src</directory></include>
    </source>
</phpunit>
```

CI pipeline parses the `coverage-clover` output and hard-fails if total code coverage drops below 80%:

```php
// Coverage enforcement logic
$xml = simplexml_load_file('coverage.xml');
$metrics = $xml->project->metrics;
$coverage = ((int)$metrics['coveredelements'] / (int)$metrics['elements']) * 100;

if ($coverage < 80) {
    echo 'Error: Code coverage is below 80%.\n';
    exit(1);
}
```

---

## Project Structure

```
royal-liquor/
├── src/                          # Backend application code
│   ├── Core/                     # Engine (Autoloader, Router, Request, Response, Session, CSRF, Database)
│   ├── DIContainer/              # DI Container + ServiceProvider base
│   ├── DTO/Requests/             # Input validation DTOs
│   └── Admin/
│       ├── API/                  # ApiServiceProvider, RouteLoader
│       ├── Controllers/          # 22 controllers
│       ├── Services/             # 21 services
│       ├── Repositories/         # 20 repositories
│       ├── Models/               # 18 domain models
│       ├── Middleware/           # Auth, CSRF, JSON, CORS, RateLimit
│       └── Exceptions/           # 7 typed exceptions
│
├── public/                       # Web root (Apache DocumentRoot)
│   ├── api/v1/index.php          # API Gateway (single entry point)
│   ├── pages/                    # Storefront pages (cart, shop, contact, etc.)
│   ├── admin/                    # Admin SPA
│   │   ├── js/                   # SPA router, renderer, page modules
│   │   └── index.php             # Admin shell
│   └── assets/
│       ├── js/                   # Frontend modules (api-helper, cart, wishlist, toast, search)
│       ├── css/                  # Compiled Tailwind output
│       └── images/               # Product images, sliders
│
├── database/schema.sql           # Full PostgreSQL schema (824 lines)
├── seed_data.sql                 # Initial data for development
├── config/                       # Environment config loader
├── storage/                      # Uploaded files (images)
├── docker-compose.yml            # One-command dev environment
├── Dockerfile                    # PHP 8.2 + Apache + pdo_pgsql
└── tailwind.config.js            # Tailwind v3.4 config
```

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2 (strict types) |
| Database | PostgreSQL 15 (JSONB, partial indexes, 36 views) |
| Frontend | ES Modules (ES6+), Vanilla JavaScript |
| Styling | Tailwind CSS v3.4 |
| Server | Apache with mod_rewrite |
| Containerization | Docker (PHP 8.2-apache + PostgreSQL 15-alpine + edoburu/pgbouncer) |
| Testing | PHPUnit (Unit & Integration tests) |

---

## Testing Architecture

The application ensures stability through automated tests located in the `tests/` directory:

1. **Unit Tests (`tests/Unit/`)**: 
   - Powered by **PHPUnit**.
   - Tests controllers in isolation by mocking services and session state (e.g., `CheckoutTest.php`, `ProductControllerTest.php`).
   - Asserts response payloads, status codes, and exception handling logic without hitting the real database.
2. **Integration Tests**:
   - Tests end-to-end flows (e.g., `BackendPipelineIntegrationTest.php`, `FrontendApiIntegrationTest.php`) mapping the middleware, controllers, and services together.
3. **Performance Benchmarking**:
   - Standalone scripts (e.g., `performance_benchmark.php`) to test raw database query speeds, particularly for complex enriched searches and stock reservation limits.

---

## Feature Status

| Feature | Status |
|---|---|
| Product Catalog and Search | Done |
| Shopping Cart (AJAX, session-synced) | Done |
| Checkout Flow | Done |
| User Auth (session + OAuth scaffold) | Done |
| Order Management + Stock Reservation | Done |
| Address Book | Done |
| Wishlist | Done |
| Cocktail Recipes + Ingredient Mapping | Done |
| Flavor Profiles + Taste Preferences | Done |
| Admin SPA (18+ pages) | Done |
| AI Recommendations (Gemini) | Done |
| AI Product Recognition | WIP (schema ready) |
| Validation Engine (DTO migration) | WIP |

---


