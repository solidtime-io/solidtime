// Package config loads process configuration from the environment.
package config

import (
	"log/slog"
	"os"
)

// Config holds runtime configuration. Defaults match docker-compose.dev.yml.
type Config struct {
	Addr         string // TOCK_ADDR, default ":8080"
	DatabaseDSN  string // TOCK_DB_DSN
	AppURL       string // TOCK_APP_URL, default http://localhost:8080
	AppEnv       string // TOCK_ENV, default "local"
	GotenbergURL string // TOCK_GOTENBERG_URL, default http://localhost:3100
	// Dev-only defaults matching upstream .env.example.
	PersonalAccessClientID     string // TOCK_PERSONAL_ACCESS_CLIENT_ID
	PersonalAccessClientSecret string // TOCK_PERSONAL_ACCESS_CLIENT_SECRET
	Logger                     *slog.Logger
}

// Load reads configuration from environment variables.
func Load() Config {
	cfg := Config{
		Addr:                       env("TOCK_ADDR", ":8080"),
		DatabaseDSN:                env("TOCK_DB_DSN", "postgres://tock:tock@127.0.0.1:54329/tock?sslmode=disable"),
		AppURL:                     env("TOCK_APP_URL", "http://localhost:8080"),
		AppEnv:                     env("TOCK_ENV", "local"),
		GotenbergURL:               env("TOCK_GOTENBERG_URL", "http://127.0.0.1:3100"),
		PersonalAccessClientID:     env("TOCK_PERSONAL_ACCESS_CLIENT_ID", "9e27f54d-5dfb-4dde-99d7-834518236c92"),
		PersonalAccessClientSecret: env("TOCK_PERSONAL_ACCESS_CLIENT_SECRET", "EL5mXp3aF8ITjcwoOXRpbSK7zGrWhW4zTDpQXTkf"),
	}
	cfg.Logger = slog.New(slog.NewJSONHandler(os.Stdout, nil))
	return cfg
}

func env(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}
