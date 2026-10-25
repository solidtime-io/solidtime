// Command tock is the single binary for the tock server: HTTP serving,
// database migrations, and the background job worker.
package main

import (
	"context"
	"errors"
	"flag"
	"fmt"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/Dvorinka/tock/internal/config"
	"github.com/Dvorinka/tock/internal/db"
	"github.com/Dvorinka/tock/internal/httpapi"
	"github.com/Dvorinka/tock/internal/jobs"
	"github.com/Dvorinka/tock/internal/seed"
)

var version = "dev"

func main() {
	if len(os.Args) < 2 {
		usage()
		os.Exit(2)
	}

	var err error
	switch os.Args[1] {
	case "serve":
		err = cmdServe(os.Args[2:])
	case "migrate":
		err = cmdMigrate(os.Args[2:])
	case "seed":
		err = cmdSeed(os.Args[2:])
	case "worker":
		err = cmdWorker(os.Args[2:])
	case "version":
		fmt.Println(version)
	case "help", "-h", "--help":
		usage()
	default:
		fmt.Fprintf(os.Stderr, "tock: unknown command %q\n", os.Args[1])
		usage()
		os.Exit(2)
	}

	if err != nil {
		fmt.Fprintf(os.Stderr, "tock: %v\n", err)
		os.Exit(1)
	}
}

func usage() {
	fmt.Fprint(os.Stderr, `usage: tock <command> [flags]

commands:
  serve     run the HTTP server (API + embedded SPA + job workers)
  migrate   apply database migrations and exit
  seed      insert demo data (OAuth clients, admin user, personal org)
  worker    run only the background job worker
  version   print version
`)
}

// signalContext cancels on SIGINT/SIGTERM so all subcommands shut down cleanly.
func signalContext() (context.Context, context.CancelFunc) {
	return signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
}

func cmdServe(args []string) error {
	fs := flag.NewFlagSet("serve", flag.ContinueOnError)
	addr := fs.String("addr", "", "listen address (env TOCK_ADDR, default :8080)")
	noJobs := fs.Bool("no-jobs", false, "disable the in-process job worker")
	if err := fs.Parse(args); err != nil {
		return err
	}

	cfg := config.Load()
	if *addr != "" {
		cfg.Addr = *addr
	}

	ctx, stop := signalContext()
	defer stop()

	pool, err := db.Open(ctx, cfg.DatabaseDSN)
	if err != nil {
		return fmt.Errorf("connect database: %w", err)
	}
	defer pool.Close()

	if err := db.Migrate(ctx, cfg.DatabaseDSN); err != nil {
		return fmt.Errorf("migrate database: %w", err)
	}

	if !*noJobs {
		worker := jobs.New(pool)
		go func() {
			if err := worker.Run(ctx); err != nil && !errors.Is(err, context.Canceled) {
				cfg.Logger.Error("job worker stopped", "error", err)
			}
		}()
	}

	srv := &http.Server{
		Addr:              cfg.Addr,
		Handler:           httpapi.NewRouter(cfg, pool),
		ReadHeaderTimeout: 10 * time.Second,
	}

	go func() {
		<-ctx.Done()
		shutdownCtx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
		defer cancel()
		_ = srv.Shutdown(shutdownCtx)
	}()

	cfg.Logger.Info("listening", "addr", cfg.Addr)
	if err := srv.ListenAndServe(); err != nil && !errors.Is(err, http.ErrServerClosed) {
		return fmt.Errorf("serve: %w", err)
	}
	return nil
}

func cmdMigrate(args []string) error {
	fs := flag.NewFlagSet("migrate", flag.ContinueOnError)
	if err := fs.Parse(args); err != nil {
		return err
	}
	cfg := config.Load()
	ctx, stop := signalContext()
	defer stop()
	if err := db.Migrate(ctx, cfg.DatabaseDSN); err != nil {
		return fmt.Errorf("migrate database: %w", err)
	}
	fmt.Println("migrations applied")
	return nil
}

func cmdSeed(args []string) error {
	fs := flag.NewFlagSet("seed", flag.ContinueOnError)
	if err := fs.Parse(args); err != nil {
		return err
	}
	cfg := config.Load()
	ctx, stop := signalContext()
	defer stop()

	pool, err := db.Open(ctx, cfg.DatabaseDSN)
	if err != nil {
		return fmt.Errorf("connect database: %w", err)
	}
	defer pool.Close()

	if err := seed.Run(ctx, pool, cfg.PersonalAccessClientID, cfg.PersonalAccessClientSecret); err != nil {
		return fmt.Errorf("seed: %w", err)
	}
	cfg.Logger.Info("seeded", "admin", "admin@example.com")
	return nil
}

func cmdWorker(args []string) error {
	fs := flag.NewFlagSet("worker", flag.ContinueOnError)
	if err := fs.Parse(args); err != nil {
		return err
	}
	cfg := config.Load()
	ctx, stop := signalContext()
	defer stop()

	pool, err := db.Open(ctx, cfg.DatabaseDSN)
	if err != nil {
		return fmt.Errorf("connect database: %w", err)
	}
	defer pool.Close()

	cfg.Logger.Info("job worker started")
	if err := jobs.New(pool).Run(ctx); err != nil && !errors.Is(err, context.Canceled) {
		return fmt.Errorf("worker: %w", err)
	}
	return nil
}
