CREATE TABLE IF NOT EXISTS webhook_subscriptions (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    url         TEXT NOT NULL,
    event_types TEXT[] NOT NULL,
    secret      VARCHAR(64) NOT NULL,
    is_active   BOOLEAN DEFAULT TRUE
);
