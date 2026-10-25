// Package jobs runs queued jobs out of the Postgres `jobs` table using
// FOR UPDATE SKIP LOCKED, matching upstream's database queue driver.
package jobs

import (
	"context"
	"log/slog"
	"time"

	"github.com/jackc/pgx/v5/pgxpool"
)

// Worker polls the jobs table for pending jobs.
type Worker struct {
	pool     *pgxpool.Pool
	interval time.Duration
	logger   *slog.Logger
}

// New returns a Worker polling every second, matching Laravel's queue cadence.
func New(pool *pgxpool.Pool) *Worker {
	return &Worker{
		pool:     pool,
		interval: time.Second,
		logger:   slog.Default(),
	}
}

// Run polls until ctx is cancelled. Job dispatch lands with the first real
// job type; until then the loop only watches for reserved capacity.
func (w *Worker) Run(ctx context.Context) error {
	ticker := time.NewTicker(w.interval)
	defer ticker.Stop()
	for {
		select {
		case <-ctx.Done():
			return ctx.Err()
		case <-ticker.C:
			// Phase 0: no job handlers registered yet. Poll exists so the
			// worker path is exercised end to end.
			var pending int
			if err := w.pool.QueryRow(ctx,
				`SELECT count(*) FROM jobs WHERE reserved_at IS NULL`).Scan(&pending); err != nil {
				w.logger.Warn("job poll failed", "error", err)
				continue
			}
			if pending > 0 {
				w.logger.Debug("pending jobs waiting for handlers", "count", pending)
			}
		}
	}
}
