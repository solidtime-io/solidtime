# AGENTS.md — tock

Fork of `solidtime-io/solidtime` (AGPL-3.0). The Laravel/PHP app in this repo
is the **reference implementation and spec** — the product is being rebuilt as
a single Go binary. Read `PLAN.md` before writing any code; it defines the
architecture, phase order, and the three hard problems (OAuth2/Passport
compat, Inertia protocol, net-new invoicing + audit log).

## Principles

1. **Port, don't redesign.** Behavior mirrors `app/`, `routes/`,
   `database/migrations/`. Read the PHP before implementing anything.
2. **Schema is sacred.** Table/column names stay identical to upstream — a
   real upstream `pg_dump` must restore and run on tock unchanged.
3. **No gates.** No plan checks, license checks, subscriptions, or feature
   flags by tier. Invoicing and audit log are built open, in-tree.
4. **Single binary.** API, worker loop, migrations ship in `tock`. Postgres
   is the job queue (`FOR UPDATE SKIP LOCKED`). No Redis, no extensions.
5. **Frontend stays.** `resources/js` is the upstream Vue 3 + Inertia SPA.
   Serve it, don't rewrite it — implement the Inertia server protocol.

## Stack

Go 1.24+, chi, pgx/v5 + sqlc, goose (`db/migrations/`), `slog`, Gotenberg
(PDF), excelize (XLSX), stdlib csv. Dev deps via docker compose: pgsql,
pgsql_test, gotenberg, mailpit, minio.

## Conventions

- Errors: typed in `internal/domain`, wrapped `fmt.Errorf("…: %w")` at edges.
- Migrations: numbered SQL, idempotent, never edit applied ones.
- No secrets or `.env` commits. No new deps without checking `go.mod` first.
- Commits: conventional (`feat(api): …`), trailer
  `Authored By: TDvorak <info@tdvorak.dev>`. Feature branch + PR to `main`.

## Verification

- `just verify` (= `go build ./...`, `go test`, `golangci-lint run`) before
  every push. `GOFLAGS=-mod=mod` is mandatory: PHP's composer `vendor/` dir
  would otherwise trigger Go vendor mode. Set via `.envrc`/justfile/CI until
  the PHP tree is deleted in Phase 9.
- Dev deps: `just dev` (docker-compose.dev.yml). `just spa` rebuilds the
  embedded SPA bundle (npm build → `internal/web/dist`). `just migrate`,
  `just seed`, `just sqlc`.
- API parity = diff generated spec vs committed `openapi.json` snapshot.
- Upstream runs locally via `docker compose` — it is the behavior oracle.
