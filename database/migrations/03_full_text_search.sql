ALTER TABLE products ADD COLUMN IF NOT EXISTS search_vector tsvector
    GENERATED ALWAYS AS (to_tsvector('english', name || ' ' || COALESCE(description, ''))) STORED;

DROP INDEX IF EXISTS idx_products_fts;
CREATE INDEX IF NOT EXISTS idx_products_search ON products USING GIN(search_vector);
