
DROP SCHEMA IF EXISTS public CASCADE;
CREATE SCHEMA public;
GRANT ALL ON SCHEMA public TO public;


CREATE TYPE address_type   AS ENUM ('billing', 'shipping', 'both');
CREATE TYPE cart_status    AS ENUM ('active', 'converted', 'abandoned', 'expired');
CREATE TYPE order_status   AS ENUM ('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded', 'failed');
CREATE TYPE payment_status AS ENUM ('pending', 'captured', 'failed', 'refunded', 'voided');


CREATE SEQUENCE order_number_seq START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;


CREATE TABLE users (
    id                BIGSERIAL PRIMARY KEY,
    name              VARCHAR(100) NOT NULL,
    email             VARCHAR(254) UNIQUE NOT NULL,
    phone             VARCHAR(20),
    password_hash     VARCHAR(255) NOT NULL,
    profile_image_url VARCHAR(500),
    oauth_provider    VARCHAR(50),
    oauth_provider_id VARCHAR(255),
    is_admin          BOOLEAN DEFAULT FALSE,
    is_active         BOOLEAN DEFAULT TRUE,
    is_anonymized     BOOLEAN DEFAULT FALSE,
    created_at        TIMESTAMPTZ DEFAULT NOW(),
    updated_at        TIMESTAMPTZ DEFAULT NOW(),
    deleted_at        TIMESTAMPTZ,
    anonymized_at     TIMESTAMPTZ,
    last_login_at     TIMESTAMPTZ
);

CREATE TABLE categories (
    id          BIGSERIAL PRIMARY KEY,
    name        VARCHAR(100) UNIQUE NOT NULL,
    slug        VARCHAR(120) UNIQUE NOT NULL,
    description TEXT,
    image_url   VARCHAR(500),
    is_active   BOOLEAN DEFAULT TRUE,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ
);

CREATE TABLE suppliers (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(100) UNIQUE NOT NULL,
    email      VARCHAR(254),
    phone      VARCHAR(20),
    address    TEXT,
    is_active  BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    deleted_at TIMESTAMPTZ
);

CREATE TABLE warehouses (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(100) UNIQUE NOT NULL,
    address    TEXT,
    phone      VARCHAR(20),
    image_url  VARCHAR(500),
    is_active  BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    deleted_at TIMESTAMPTZ
);

CREATE TABLE products (
    id          BIGSERIAL PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    slug        VARCHAR(220) UNIQUE NOT NULL,
    description TEXT,
    price_cents INTEGER NOT NULL CHECK (price_cents > 0),
    image_url   VARCHAR(500),
    category_id BIGINT NOT NULL REFERENCES categories(id),
    supplier_id BIGINT REFERENCES suppliers(id),
    is_active   BOOLEAN DEFAULT TRUE,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ
);

CREATE TABLE carts (
    id           BIGSERIAL PRIMARY KEY,
    user_id      BIGINT REFERENCES users(id) ON DELETE SET NULL,
    session_id   VARCHAR(64) NOT NULL,
    status       cart_status DEFAULT 'active',
    total_cents  INTEGER DEFAULT 0,
    item_count   INTEGER DEFAULT 0,
    created_at   TIMESTAMPTZ DEFAULT NOW(),
    updated_at   TIMESTAMPTZ DEFAULT NOW(),
    converted_at TIMESTAMPTZ,
    abandoned_at TIMESTAMPTZ
);

CREATE TABLE cart_items (
    id                 BIGSERIAL PRIMARY KEY,
    cart_id            BIGINT NOT NULL REFERENCES carts(id) ON DELETE CASCADE,
    product_id         BIGINT NOT NULL REFERENCES products(id),
    quantity           INTEGER NOT NULL CHECK (quantity > 0),
    price_at_add_cents INTEGER NOT NULL,
    created_at         TIMESTAMPTZ DEFAULT NOW(),
    updated_at         TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(cart_id, product_id)
);

CREATE TABLE orders (
    id                  BIGSERIAL PRIMARY KEY,
    order_number        VARCHAR(20) UNIQUE NOT NULL DEFAULT ('RL-ORD-' || nextval('order_number_seq'::regclass)),
    cart_id             BIGINT NOT NULL REFERENCES carts(id),
    user_id             BIGINT REFERENCES users(id) ON DELETE SET NULL,
    status              order_status DEFAULT 'pending',
    total_cents         INTEGER NOT NULL,
    shipping_address_id BIGINT, -- FK added after user_addresses table
    billing_address_id  BIGINT,  -- FK added after user_addresses table
    notes               TEXT,
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW(),
    paid_at             TIMESTAMPTZ,
    shipped_at          TIMESTAMPTZ,
    delivered_at        TIMESTAMPTZ,
    cancelled_at        TIMESTAMPTZ
);

CREATE TABLE order_items (
    id                BIGSERIAL PRIMARY KEY,
    order_id          BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id        BIGINT NOT NULL REFERENCES products(id),
    product_name      TEXT NOT NULL,
    product_image_url TEXT,
    price_cents       INTEGER NOT NULL,
    quantity          INTEGER NOT NULL CHECK (quantity > 0),
    warehouse_id      BIGINT REFERENCES warehouses(id),
    created_at        TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE stock (
    id           BIGSERIAL PRIMARY KEY,
    product_id   BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    warehouse_id BIGINT NOT NULL REFERENCES warehouses(id),
    quantity     INTEGER NOT NULL DEFAULT 0 CHECK (quantity >= 0),
    reserved     INTEGER NOT NULL DEFAULT 0 CHECK (reserved >= 0),
    created_at   TIMESTAMPTZ DEFAULT NOW(),
    updated_at   TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(product_id, warehouse_id)
);

CREATE TABLE payments (
    id               BIGSERIAL PRIMARY KEY,
    order_id         BIGINT NOT NULL REFERENCES orders(id) ON DELETE RESTRICT,
    amount_cents     INTEGER NOT NULL,
    currency         CHAR(3) DEFAULT 'LKR',
    gateway          VARCHAR(50) NOT NULL,
    gateway_order_id VARCHAR(255),
    transaction_id   VARCHAR(255),
    status           payment_status DEFAULT 'pending',
    payload          JSONB,
    created_at       TIMESTAMPTZ DEFAULT NOW(),
    updated_at       TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE user_addresses (
    id             BIGSERIAL PRIMARY KEY,
    user_id        BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    address_type   address_type NOT NULL DEFAULT 'both',
    recipient_name VARCHAR(100),
    phone          VARCHAR(20),
    address_line1  VARCHAR(255) NOT NULL,
    address_line2  VARCHAR(255),
    city           VARCHAR(100) NOT NULL,
    state          VARCHAR(100),
    postal_code    VARCHAR(20) NOT NULL,
    country        VARCHAR(100) DEFAULT 'Sri Lanka',
    is_default     BOOLEAN DEFAULT FALSE,
    created_at     TIMESTAMPTZ DEFAULT NOW(),
    updated_at     TIMESTAMPTZ DEFAULT NOW(),
    deleted_at     TIMESTAMPTZ,
    UNIQUE(user_id, address_type, is_default)
);

CREATE TABLE flavor_profiles (
    product_id BIGINT PRIMARY KEY REFERENCES products(id) ON DELETE CASCADE,
    sweetness  INTEGER DEFAULT 5 CHECK (sweetness BETWEEN 0 AND 10),
    bitterness INTEGER DEFAULT 5 CHECK (bitterness BETWEEN 0 AND 10),
    strength   INTEGER DEFAULT 5 CHECK (strength BETWEEN 0 AND 10),
    smokiness  INTEGER DEFAULT 5 CHECK (smokiness BETWEEN 0 AND 10),
    fruitiness INTEGER DEFAULT 5 CHECK (fruitiness BETWEEN 0 AND 10),
    spiciness  INTEGER DEFAULT 5 CHECK (spiciness BETWEEN 0 AND 10),
    tags       TEXT[],
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE feedback (
    id                   BIGSERIAL PRIMARY KEY,
    user_id              BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    product_id           BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    rating               INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment              TEXT,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    is_active            BOOLEAN DEFAULT TRUE,
    created_at           TIMESTAMPTZ DEFAULT NOW(),
    updated_at           TIMESTAMPTZ DEFAULT NOW(),
    deleted_at           TIMESTAMPTZ
);

CREATE TABLE cocktail_recipes (
    id               BIGSERIAL PRIMARY KEY,
    name             VARCHAR(200) NOT NULL,
    description      TEXT,
    instructions     TEXT NOT NULL,
    image_url        VARCHAR(500),
    difficulty       VARCHAR(20) DEFAULT 'easy',
    preparation_time INTEGER,
    serves           INTEGER DEFAULT 1,
    is_active        BOOLEAN DEFAULT TRUE,
    created_at       TIMESTAMPTZ DEFAULT NOW(),
    updated_at       TIMESTAMPTZ DEFAULT NOW(),
    deleted_at       TIMESTAMPTZ
);

CREATE TABLE recipe_ingredients (
    id          BIGSERIAL PRIMARY KEY,
    recipe_id   BIGINT NOT NULL REFERENCES cocktail_recipes(id) ON DELETE CASCADE,
    product_id  BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity    NUMERIC(10,2) NOT NULL CHECK (quantity > 0),
    unit        VARCHAR(50) NOT NULL,
    is_optional BOOLEAN DEFAULT FALSE,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMP DEFAULT NOW(),
    UNIQUE(recipe_id, product_id)
);

CREATE TABLE user_preferences (
    id                   BIGSERIAL PRIMARY KEY,
    user_id              BIGINT UNIQUE NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    preferred_sweetness  INTEGER DEFAULT 5 CHECK (preferred_sweetness BETWEEN 0 AND 10),
    preferred_bitterness INTEGER DEFAULT 5 CHECK (preferred_bitterness BETWEEN 0 AND 10),
    preferred_strength   INTEGER DEFAULT 5 CHECK (preferred_strength BETWEEN 0 AND 10),
    preferred_smokiness  INTEGER DEFAULT 5 CHECK (preferred_smokiness BETWEEN 0 AND 10),
    preferred_fruitiness INTEGER DEFAULT 5 CHECK (preferred_fruitiness BETWEEN 0 AND 10),
    preferred_spiciness  INTEGER DEFAULT 5 CHECK (preferred_spiciness BETWEEN 0 AND 10),
    favorite_categories  INTEGER[],
    created_at           TIMESTAMPTZ DEFAULT NOW(),
    updated_at           TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE wishlist_items (
    id         BIGSERIAL PRIMARY KEY,
    user_id    BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(user_id, product_id)
);

ALTER TABLE orders ADD CONSTRAINT orders_shipping_address_id_fkey FOREIGN KEY (shipping_address_id) REFERENCES user_addresses(id);
ALTER TABLE orders ADD CONSTRAINT orders_billing_address_id_fkey FOREIGN KEY (billing_address_id) REFERENCES user_addresses(id);


CREATE INDEX idx_users_email_active            ON users(email) WHERE is_active = TRUE AND deleted_at IS NULL;
CREATE INDEX idx_products_active               ON products(id) WHERE is_active = TRUE AND deleted_at IS NULL;
CREATE INDEX idx_products_category             ON products(category_id) WHERE is_active = TRUE;
CREATE INDEX idx_products_price                ON products(price_cents);
CREATE INDEX idx_products_slug_active          ON products(slug) WHERE is_active = TRUE;
CREATE INDEX idx_products_name                 ON products(name);
CREATE INDEX idx_stock_available              ON stock(product_id) WHERE (quantity > reserved);
CREATE INDEX idx_stock_low                     ON stock(quantity ASC) WHERE (quantity < 50);
CREATE UNIQUE INDEX idx_carts_active_user      ON carts(user_id) WHERE (status = 'active');
CREATE UNIQUE INDEX idx_carts_active_session   ON carts(session_id) WHERE (status = 'active');
CREATE INDEX idx_orders_user_date              ON orders(user_id, created_at DESC);
CREATE INDEX idx_orders_status_date            ON orders(status, created_at DESC);
CREATE INDEX idx_order_items_product           ON order_items(product_id);
CREATE INDEX idx_flavor_sweetness              ON flavor_profiles(sweetness);
CREATE INDEX idx_flavor_strength               ON flavor_profiles(strength);
CREATE INDEX idx_feedback_product_active       ON feedback(product_id) WHERE (is_active = true);
CREATE INDEX idx_wishlist_user                 ON wishlist_items(user_id);

CREATE INDEX idx_carts_user                 ON carts(user_id);
CREATE INDEX idx_order_items_order          ON order_items(order_id);
CREATE INDEX idx_order_items_warehouse      ON order_items(warehouse_id);
CREATE INDEX idx_products_supplier          ON products(supplier_id);
CREATE INDEX idx_recipe_ingredients_recipe  ON recipe_ingredients(recipe_id);
CREATE INDEX idx_payments_order             ON payments(order_id);

CREATE INDEX idx_products_fts ON products USING GIN (to_tsvector('english', name || ' ' || COALESCE(description, '')));
CREATE INDEX idx_cocktail_recipes_fts ON cocktail_recipes USING GIN (to_tsvector('english', name || ' ' || COALESCE(instructions, '')));

CREATE INDEX idx_orders_user_status         ON orders(user_id, status);
CREATE INDEX idx_users_oauth                ON users(oauth_provider, oauth_provider_id);

CREATE INDEX idx_payments_payload           ON payments USING GIN (payload);

CREATE VIEW admin_list_users AS
SELECT
    u.id, u.name, u.email, u.phone, u.is_admin, u.is_active, u.created_at, u.last_login_at,
    (SELECT count(*) FROM orders WHERE user_id = u.id) AS order_count,
    (SELECT COALESCE(sum(total_cents), 0) FROM orders WHERE user_id = u.id AND status IN ('paid', 'delivered')) AS lifetime_value_cents
FROM users u
WHERE u.deleted_at IS NULL AND u.is_anonymized = FALSE;

CREATE VIEW admin_detail_users AS
SELECT
    u.id, u.name, u.email, u.phone, u.profile_image_url, u.is_admin, u.is_active, u.is_anonymized, u.created_at, u.updated_at, u.last_login_at, u.anonymized_at,
    (SELECT count(*) FROM orders WHERE user_id = u.id) AS total_orders,
    (SELECT count(*) FROM orders WHERE user_id = u.id AND status = 'delivered') AS completed_orders,
    (SELECT count(*) FROM orders WHERE user_id = u.id AND status IN ('pending', 'processing')) AS pending_orders,
    (SELECT count(*) FROM orders WHERE user_id = u.id AND status = 'cancelled') AS cancelled_orders,
    (SELECT COALESCE(sum(total_cents), 0) FROM orders WHERE user_id = u.id AND status IN ('paid', 'delivered')) AS lifetime_value_cents,
    (SELECT COALESCE(avg(total_cents), 0) FROM orders WHERE user_id = u.id AND status IN ('paid', 'delivered')) AS avg_order_value_cents,
    (SELECT max(created_at) FROM orders WHERE user_id = u.id) AS last_order_date,
    (SELECT count(*) FROM feedback WHERE user_id = u.id) AS feedback_count,
    (SELECT count(*) FROM carts WHERE user_id = u.id AND status = 'active') AS active_carts,
    (SELECT count(*) FROM carts WHERE user_id = u.id AND status = 'abandoned') AS abandoned_carts,
    (SELECT count(*) FROM user_addresses WHERE user_id = u.id AND deleted_at IS NULL) AS address_count,
    (SELECT string_agg(DISTINCT p.gateway, ', ') FROM payments p JOIN orders o ON p.order_id = o.id WHERE o.user_id = u.id) AS payment_gateways_used,
    (SELECT json_agg(row_to_json(t)) FROM (SELECT order_number, status, total_cents, created_at FROM orders WHERE user_id = u.id ORDER BY created_at DESC LIMIT 5) t) AS recent_orders
FROM users u
WHERE u.deleted_at IS NULL AND u.is_anonymized = FALSE;

CREATE VIEW admin_list_categories AS
SELECT
    c.id, c.name, c.slug, c.is_active, c.created_at,
    (SELECT count(*) FROM products p WHERE p.category_id = c.id AND p.is_active = TRUE) AS product_count,
    (SELECT COALESCE(sum(s.quantity - s.reserved), 0) FROM stock s JOIN products p ON s.product_id = p.id WHERE p.category_id = c.id) AS total_stock
FROM categories c
WHERE c.deleted_at IS NULL;

CREATE VIEW admin_detail_categories AS
SELECT
    c.id, c.name, c.slug, c.description, c.image_url, c.is_active, c.created_at, c.updated_at,
    (SELECT count(*) FROM products p WHERE p.category_id = c.id) AS total_products,
    (SELECT count(*) FROM products p WHERE p.category_id = c.id AND p.is_active = TRUE) AS active_products,
    (SELECT avg(p.price_cents) FROM products p WHERE p.category_id = c.id) AS avg_price_cents,
    (SELECT min(p.price_cents) FROM products p WHERE p.category_id = c.id) AS min_price_cents,
    (SELECT max(p.price_cents) FROM products p WHERE p.category_id = c.id) AS max_price_cents,
    (SELECT COALESCE(sum(s.quantity), 0) FROM stock s JOIN products p ON s.product_id = p.id WHERE p.category_id = c.id) AS total_inventory,
    (SELECT COALESCE(sum(s.reserved), 0) FROM stock s JOIN products p ON s.product_id = p.id WHERE p.category_id = c.id) AS total_reserved,
    (SELECT count(*) FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE p.category_id = c.id) AS total_sales,
    (SELECT json_agg(row_to_json(t)) FROM (SELECT id, name, slug, price_cents, is_active FROM products WHERE category_id = c.id ORDER BY name LIMIT 10) t) AS top_products
FROM categories c
WHERE c.deleted_at IS NULL;

CREATE VIEW admin_list_products AS
SELECT
    p.id, p.name, p.slug, p.price_cents, cat.name as category_name, sup.name as supplier_name, p.is_active, p.created_at,
    (SELECT COALESCE(sum(s.quantity - s.reserved), 0) FROM stock s WHERE s.product_id = p.id) as available_stock,
    (SELECT avg(rating) FROM feedback f WHERE f.product_id = p.id AND f.is_active = TRUE) as avg_rating
FROM products p
LEFT JOIN categories cat ON p.category_id = cat.id
LEFT JOIN suppliers sup ON p.supplier_id = sup.id
WHERE p.deleted_at IS NULL;

CREATE VIEW admin_detail_products AS
SELECT
    p.id, p.name, p.slug, p.description, p.price_cents, p.image_url, p.category_id, cat.name as category_name, cat.slug as category_slug, p.supplier_id, sup.name as supplier_name, sup.email as supplier_email, sup.phone as supplier_phone, p.is_active, p.created_at, p.updated_at,
    (SELECT json_agg(row_to_json(t)) FROM (SELECT w.name as warehouse_name, s.quantity, s.reserved, (s.quantity - s.reserved) as available FROM stock s JOIN warehouses w ON s.warehouse_id = w.id WHERE s.product_id = p.id) t) as stock_by_warehouse,
    (SELECT COALESCE(sum(s.quantity), 0) FROM stock s WHERE s.product_id = p.id) as total_quantity,
    (SELECT COALESCE(sum(s.reserved), 0) FROM stock s WHERE s.product_id = p.id) as total_reserved,
    (SELECT COALESCE(sum(s.quantity - s.reserved), 0) FROM stock s WHERE s.product_id = p.id) as total_available,
    (SELECT count(*) FROM order_items WHERE product_id = p.id) as times_ordered,
    (SELECT COALESCE(sum(quantity), 0) FROM order_items WHERE product_id = p.id) as total_sold,
    (SELECT COALESCE(sum(quantity * price_cents), 0) FROM order_items WHERE product_id = p.id) as total_revenue_cents,
    (SELECT count(*) FROM feedback WHERE product_id = p.id AND is_active = TRUE) as feedback_count,
    (SELECT avg(rating) FROM feedback WHERE product_id = p.id AND is_active = TRUE) as avg_rating,
    (SELECT row_to_json(fp) FROM flavor_profiles fp WHERE fp.product_id = p.id) as flavor_profile,
    (SELECT json_agg(row_to_json(t)) FROM (SELECT o.order_number, oi.quantity, oi.price_cents, o.created_at FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id ORDER BY o.created_at DESC LIMIT 10) t) as recent_orders
FROM products p
LEFT JOIN categories cat ON p.category_id = cat.id
LEFT JOIN suppliers sup ON p.supplier_id = sup.id
WHERE p.deleted_at IS NULL;

CREATE VIEW admin_list_carts AS
SELECT c.id, c.session_id, c.status, c.total_cents, c.item_count, c.created_at, c.updated_at, u.name as user_name, u.email as user_email
FROM carts c
LEFT JOIN users u ON c.user_id = u.id;

CREATE VIEW admin_detail_carts AS
SELECT
    c.id, c.session_id, c.status, c.total_cents, c.item_count, c.created_at, c.updated_at, c.converted_at, c.abandoned_at, c.user_id, u.name as user_name, u.email as user_email,
    (SELECT json_agg(row_to_json(t)) FROM (SELECT ci.id, ci.product_id, p.name as product_name, ci.quantity, ci.price_at_add_cents FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.cart_id = c.id) t) as items,
    (SELECT order_number FROM orders WHERE cart_id = c.id LIMIT 1) as converted_order_number
FROM carts c
LEFT JOIN users u ON c.user_id = u.id;

CREATE VIEW admin_list_orders AS
SELECT o.id, o.order_number, o.status, o.total_cents, o.created_at, u.name as user_name, u.email as user_email,
    (SELECT count(*) FROM order_items WHERE order_id = o.id) as item_count
FROM orders o
LEFT JOIN users u ON o.user_id = u.id;

CREATE VIEW admin_detail_orders AS
SELECT
    o.id, o.order_number, o.status, o.total_cents, o.notes, o.created_at, o.updated_at, o.paid_at, o.shipped_at, o.delivered_at, o.cancelled_at, o.user_id, u.name as user_name, u.email as user_email, u.phone as user_phone, o.shipping_address_id,
    (SELECT row_to_json(sa) FROM (SELECT recipient_name, phone, address_line1, address_line2, city, state, postal_code, country FROM user_addresses WHERE id = o.shipping_address_id) sa) as shipping_address,
    o.billing_address_id,
    (SELECT row_to_json(ba) FROM (SELECT recipient_name, phone, address_line1, address_line2, city, state, postal_code, country FROM user_addresses WHERE id = o.billing_address_id) ba) as billing_address,
    (SELECT json_agg(row_to_json(t)) FROM (SELECT oi.id, oi.product_id, oi.product_name, oi.product_image_url, oi.quantity, oi.price_cents, oi.warehouse_id, w.name as warehouse_name FROM order_items oi LEFT JOIN warehouses w ON oi.warehouse_id = w.id WHERE oi.order_id = o.id) t) as items,
    (SELECT count(*) FROM order_items WHERE order_id = o.id) as item_count,
    (SELECT json_agg(row_to_json(t)) FROM (SELECT id, amount_cents, currency, gateway, transaction_id, status, created_at FROM payments WHERE order_id = o.id) t) as payments,
    o.cart_id, (SELECT session_id FROM carts WHERE id = o.cart_id) as cart_session_id
FROM orders o
LEFT JOIN users u ON o.user_id = u.id;

CREATE VIEW admin_list_stock AS
SELECT s.id, s.product_id, p.name as product_name, s.warehouse_id, w.name as warehouse_name, s.quantity, s.reserved, (s.quantity - s.reserved) as available, s.updated_at
FROM stock s
JOIN products p ON s.product_id = p.id
JOIN warehouses w ON s.warehouse_id = w.id
WHERE p.deleted_at IS NULL AND w.deleted_at IS NULL;

CREATE VIEW admin_detail_stock AS
SELECT
    s.id, s.product_id, p.name as product_name, p.slug as product_slug, p.price_cents, s.warehouse_id, w.name as warehouse_name, w.address as warehouse_address, s.quantity, s.reserved, (s.quantity - s.reserved) as available, s.created_at, s.updated_at,
    ((p.price_cents * (s.quantity - s.reserved))::numeric / 100.0) as inventory_value,
    (SELECT json_agg(row_to_json(t)) FROM (SELECT o.order_number, oi.quantity, o.status, o.created_at FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = s.product_id AND oi.warehouse_id = s.warehouse_id ORDER BY o.created_at DESC LIMIT 10) t) as recent_movements
FROM stock s
JOIN products p ON s.product_id = p.id
JOIN warehouses w ON s.warehouse_id = w.id
WHERE p.deleted_at IS NULL AND w.deleted_at IS NULL;

CREATE VIEW admin_list_suppliers AS
SELECT s.id, s.name, s.email, s.phone, s.is_active, s.created_at,
    (SELECT count(*) FROM products WHERE supplier_id = s.id AND is_active = TRUE) as product_count
FROM suppliers s
WHERE s.deleted_at IS NULL;

CREATE VIEW admin_detail_suppliers AS
SELECT
    s.id, s.name, s.email, s.phone, s.address, s.is_active, s.created_at, s.updated_at,
    (SELECT count(*) FROM products WHERE supplier_id = s.id) as total_products,
    (SELECT count(*) FROM products WHERE supplier_id = s.id AND is_active = TRUE) as active_products,
    (SELECT avg(price_cents) FROM products WHERE supplier_id = s.id) as avg_product_price_cents,
    (SELECT COALESCE(sum(st.quantity), 0) FROM stock st JOIN products p ON st.product_id = p.id WHERE p.supplier_id = s.id) as total_inventory,
    (SELECT json_agg(row_to_json(t)) FROM (SELECT id, name, slug, price_cents, is_active FROM products WHERE supplier_id = s.id ORDER BY name LIMIT 20) t) as products
FROM suppliers s
WHERE s.deleted_at IS NULL;

CREATE VIEW admin_list_warehouses AS
SELECT w.id, w.name, w.phone, w.is_active, w.created_at,
    (SELECT COALESCE(sum(quantity - reserved), 0) FROM stock WHERE warehouse_id = w.id) as available_stock,
    (SELECT count(DISTINCT product_id) FROM stock WHERE warehouse_id = w.id) as unique_products
FROM warehouses w
WHERE w.deleted_at IS NULL;

CREATE VIEW admin_detail_warehouses AS
SELECT
    w.id, w.name, w.address, w.phone, w.image_url, w.is_active, w.created_at, w.updated_at,
    (SELECT count(*) FROM stock WHERE warehouse_id = w.id) as total_stock_entries,
    (SELECT count(DISTINCT product_id) FROM stock WHERE warehouse_id = w.id) as unique_products,
    (SELECT COALESCE(sum(quantity), 0) FROM stock WHERE warehouse_id = w.id) as total_quantity,
    (SELECT COALESCE(sum(reserved), 0) FROM stock WHERE warehouse_id = w.id) as total_reserved,
    (SELECT COALESCE(sum(quantity - reserved), 0) FROM stock WHERE warehouse_id = w.id) as total_available,
    (SELECT json_agg(row_to_json(t)) FROM (SELECT p.name as product_name, s.quantity, s.reserved, (s.quantity - s.reserved) as available FROM stock s JOIN products p ON s.product_id = p.id WHERE s.warehouse_id = w.id AND s.quantity < 20 ORDER BY s.quantity LIMIT 10) t) as low_stock_items,
    (SELECT json_agg(row_to_json(t)) FROM (SELECT DISTINCT o.order_number, o.status, o.created_at FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE oi.warehouse_id = w.id ORDER BY o.created_at DESC LIMIT 10) t) as recent_shipments
FROM warehouses w
WHERE w.deleted_at IS NULL;

CREATE VIEW admin_list_cocktail_recipes AS
SELECT cr.id, cr.name, cr.difficulty, cr.preparation_time, cr.serves, cr.is_active, cr.created_at,
    (SELECT count(*) FROM recipe_ingredients WHERE recipe_id = cr.id) as ingredient_count
FROM cocktail_recipes cr
WHERE cr.deleted_at IS NULL;

CREATE VIEW admin_detail_cocktail_recipes AS
SELECT
    cr.id, cr.name, cr.description, cr.instructions, cr.image_url, cr.difficulty, cr.preparation_time, cr.serves, cr.is_active, cr.created_at, cr.updated_at,
    (SELECT json_agg(row_to_json(t)) FROM (SELECT ri.id, ri.product_id, p.name as product_name, ri.quantity, ri.unit, ri.is_optional FROM recipe_ingredients ri JOIN products p ON ri.product_id = p.id WHERE ri.recipe_id = cr.id) t) as ingredients,
    (SELECT count(*) FROM recipe_ingredients WHERE recipe_id = cr.id) as ingredient_count,
    (SELECT COALESCE(sum(p.price_cents::numeric * ri.quantity), 0) FROM recipe_ingredients ri JOIN products p ON ri.product_id = p.id WHERE ri.recipe_id = cr.id AND ri.is_optional = FALSE) as estimated_cost_cents
FROM cocktail_recipes cr
WHERE cr.deleted_at IS NULL;

CREATE VIEW admin_list_feedback AS
SELECT f.id, f.rating, f.is_active, f.created_at, u.name as user_name, p.name as product_name, f.is_verified_purchase
FROM feedback f
JOIN users u ON f.user_id = u.id
JOIN products p ON f.product_id = p.id
WHERE f.deleted_at IS NULL;

CREATE VIEW admin_detail_feedback AS
SELECT
    f.id, f.rating, f.comment, f.is_verified_purchase, f.is_active, f.created_at, f.updated_at, f.user_id, u.name as user_name, u.email as user_email, f.product_id, p.name as product_name, p.slug as product_slug,
    (SELECT count(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = f.product_id AND o.user_id = f.user_id AND o.status IN ('paid', 'delivered')) as purchase_count
FROM feedback f
JOIN users u ON f.user_id = u.id
JOIN products p ON f.product_id = p.id
WHERE f.deleted_at IS NULL;

CREATE VIEW admin_list_flavor_profiles AS
SELECT fp.product_id, p.name as product_name, p.slug as product_slug, fp.sweetness, fp.bitterness, fp.strength
FROM flavor_profiles fp
JOIN products p ON fp.product_id = p.id
WHERE p.deleted_at IS NULL;

CREATE VIEW admin_detail_flavor_profiles AS
SELECT
    fp.product_id, p.name as product_name, p.slug as product_slug, fp.sweetness, fp.bitterness, fp.strength, fp.smokiness, fp.fruitiness, fp.spiciness, fp.tags,
    (SELECT avg(rating) FROM feedback WHERE product_id = fp.product_id AND is_active = true) as avg_rating,
    (SELECT count(*) FROM feedback WHERE product_id = fp.product_id AND is_active = true) as feedback_count
FROM flavor_profiles fp
JOIN products p ON fp.product_id = p.id
WHERE p.deleted_at IS NULL;

CREATE VIEW admin_list_payments AS
SELECT p.id, p.order_id, o.order_number, p.amount_cents, p.gateway, p.status, p.created_at
FROM payments p
JOIN orders o ON p.order_id = o.id;

CREATE VIEW admin_detail_payments AS
SELECT
    p.id, p.order_id, o.order_number, o.status as order_status, p.amount_cents, p.currency, p.gateway, p.gateway_order_id, p.transaction_id, p.status, p.payload, p.created_at, o.user_id, u.name as user_name, u.email as user_email, o.total_cents as order_total_cents
FROM payments p
JOIN orders o ON p.order_id = o.id
LEFT JOIN users u ON o.user_id = u.id;

CREATE VIEW admin_list_user_addresses AS
SELECT ua.id, ua.user_id, u.name as user_name, u.email as user_email, ua.address_type, ua.city, ua.country, ua.is_default, ua.created_at
FROM user_addresses ua
JOIN users u ON ua.user_id = u.id
WHERE ua.deleted_at IS NULL;

CREATE VIEW admin_detail_user_addresses AS
SELECT
    ua.id, ua.user_id, u.name as user_name, u.email as user_email, ua.address_type, ua.recipient_name, ua.phone, ua.address_line1, ua.address_line2, ua.city, ua.state, ua.postal_code, ua.country, ua.is_default, ua.created_at, ua.updated_at,
    (SELECT count(*) FROM orders WHERE shipping_address_id = ua.id) as used_as_shipping,
    (SELECT count(*) FROM orders WHERE billing_address_id = ua.id) as used_as_billing
FROM user_addresses ua
JOIN users u ON ua.user_id = u.id
WHERE ua.deleted_at IS NULL;


CREATE MATERIALIZED VIEW mvw_sales_by_category AS
SELECT 
    c.id AS category_id,
    c.name AS category_name,
    COUNT(DISTINCT o.id) AS total_orders,
    SUM(oi.quantity) AS units_sold,
    SUM(oi.quantity * oi.price_cents) AS total_revenue_cents
FROM categories c
JOIN products p ON p.category_id = c.id
JOIN order_items oi ON oi.product_id = p.id
JOIN orders o ON o.id = oi.order_id
WHERE o.status IN ('paid', 'delivered')
GROUP BY c.id, c.name;

CREATE UNIQUE INDEX idx_mvw_sales_by_category_id ON mvw_sales_by_category(category_id);

CREATE MATERIALIZED VIEW mvw_daily_revenue AS
SELECT 
    DATE(created_at) AS order_date,
    COUNT(id) AS order_count,
    SUM(total_cents) AS daily_revenue_cents
FROM orders
WHERE status IN ('paid', 'delivered')
GROUP BY DATE(created_at)
ORDER BY order_date DESC;

CREATE UNIQUE INDEX idx_mvw_daily_revenue_date ON mvw_daily_revenue(order_date);

CREATE TABLE idempotency_keys (
    key             VARCHAR(64) PRIMARY KEY,
    endpoint        VARCHAR(128) NOT NULL,
    response_body   JSONB NOT NULL,
    status_code     INT NOT NULL,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_idempotency_created ON idempotency_keys(created_at);

CREATE TABLE jobs (
    id          BIGSERIAL PRIMARY KEY,
    job_type    VARCHAR(64) NOT NULL,
    payload     JSONB NOT NULL,
    status      VARCHAR(16) DEFAULT 'pending',
    attempts    INT DEFAULT 0,
    run_at      TIMESTAMPTZ DEFAULT NOW(),
    created_at  TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_jobs_pending ON jobs(run_at) WHERE status = 'pending';

ALTER TABLE products ADD COLUMN search_vector tsvector
    GENERATED ALWAYS AS (to_tsvector('english', name || ' ' || COALESCE(description, ''))) STORED;
DROP INDEX IF EXISTS idx_products_fts;
CREATE INDEX idx_products_search ON products USING GIN(search_vector);

CREATE TABLE webhook_subscriptions (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    url         TEXT NOT NULL,
    event_types TEXT[] NOT NULL,
    secret      VARCHAR(64) NOT NULL,
    is_active   BOOLEAN DEFAULT TRUE
);
