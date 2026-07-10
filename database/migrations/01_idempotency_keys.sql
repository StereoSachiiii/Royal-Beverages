CREATE TABLE IF NOT EXISTS idempotency_keys (
    key             VARCHAR(64) PRIMARY KEY,
    endpoint        VARCHAR(128) NOT NULL,
    response_body   JSONB NOT NULL,
    status_code     INT NOT NULL,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_idempotency_created ON idempotency_keys(created_at);
