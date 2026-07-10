CREATE TABLE IF NOT EXISTS jobs (
    id          BIGSERIAL PRIMARY KEY,
    job_type    VARCHAR(64) NOT NULL,
    payload     JSONB NOT NULL,
    status      VARCHAR(16) DEFAULT 'pending', -- pending, processing, done, failed
    attempts    INT DEFAULT 0,
    run_at      TIMESTAMPTZ DEFAULT NOW(),
    created_at  TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_jobs_pending ON jobs(run_at) WHERE status = 'pending';
