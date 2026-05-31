# Royal Beverages — E-Commerce Platform

An e-commerce platform built with Vanilla PHP 8.2 and JavaScript. No frameworks.
Custom MVC, Reflection-based DI container, regex router, multi-warehouse stock.

For the full technical breakdown, see [ARCHITECTURE.md](ARCHITECTURE.md).



## Key Features

- **Custom-Built MVC **: Modern PHP architecture implemented from scratch without external frameworks.
- **Automated Dependency Injection**: Reflection-based DI container for automatic class wiring.
- **Multi-Warehouse Stock Engine**: Robust FIFO inventory management with row-level locking.
- **Admin SPA Dashboard**: A high-performance, framework-less Single Page Application for management.
- **Advanced Security**: Integrated CSRF protection, session-based rate limiting, and SQL injection prevention.
- **PostgreSQL Optimized**: Using complex views and efficient indexing for high-performance data retrieval.



## Quick Start

### Requirements
- [Docker Desktop](https://www.docker.com/products/docker-desktop/)

### 1. Start the environment
```bash
docker-compose up -d
```
This creates the PostgreSQL database, loads the schema (`database/schema.sql`), and populates seed data (`seed_data.sql`).

### 2. Build assets
```bash
npm install
npm run build:css
```

### 3. Access
- **Storefront**: http://localhost
- **Admin**: http://localhost/admin/index.php
  - Email: `admin@royal-liquor.com`
  - Password: `Admin123!`

---

## Development

### CSS
Tailwind v3.4. No manual CSS files.
- Watch: `npm run dev:css`
- Build: `npm run build:css`

### Backend
Custom PSR-4 autoloader. Classes added to `src/` are loaded automatically via `src/Core/bootstrap.php`.

The Router is decoupled from the global `$GLOBALS['container']` state by accepting the DI Container instance via the constructor, supporting clean controller-string resolution (`[Controller::class, 'method']`) dynamically during dispatch.

Routes are automatically discovered and loaded dynamically via `glob()` scanning of the route definition files, eliminating the need to manually update a hardcoded route registry.

---

## Security
- **CSRF**: Token validated on all POST/PUT/DELETE requests.
- **Rate Limiting**: Per-session sliding window on sensitive endpoints.
- **Strict Types**: Enforced in all `src/` files.
- **Passwords**: BCrypt via `password_hash()`.
- **SQL**: Prepared statements everywhere.


## Screenshots



<img src="docs/images/image.png" width="800" />
<img src="docs/images/image copy.png" width="800" />
<img src="docs/images/image copy 2.png" width="800" />
<img src="docs/images/image copy 3.png" width="800" />
<img src="docs/images/image copy 4.png" width="800" />
<img src="docs/images/image copy 5.png" width="800" />
<img src="docs/images/image copy 6.png" width="800" />
<img src="docs/images/image copy 7.png" width="800" />
<img src="docs/images/image copy 8.png" width="800" />
<img src="docs/images/image copy 9.png" width="800" />
<img src="docs/images/image copy 10.png" width="800" />
<img src="docs/images/image copy 11.png" width="800" />
<img src="docs/images/image copy 12.png" width="800" />
<img src="docs/images/image copy 13.png" width="800" />
<img src="docs/images/image copy 14.png" width="800" />
<img src="docs/images/image copy 15.png" width="800" />
<img src="docs/images/image copy 16.png" width="800" />
<img src="docs/images/image copy 17.png" width="800" />
<img src="docs/images/image copy 18.png" width="800" />
<img src="docs/images/image copy 19.png" width="800" />
<img src="docs/images/image copy 20.png" width="800" />

</details>
