# tock — solidtime, rebuilt in Go

`tock` is a fork of [solidtime](https://github.com/solidtime-io/solidtime)
(AGPL-3.0) with the PHP/Laravel backend replaced by a single Go binary, and
every paywall-gated feature built natively and shipped to everyone.

The PHP application in this repository is the **reference implementation and
spec**. Read it; do not redesign from memory. When upstream `app/` code and
this plan disagree, the PHP code wins for behavior, this plan wins for
technology.

## Why

- Laravel + Octane + Passport + Filament + extensions is a heavy, sprawling
  stack for what is fundamentally a CRUD + aggregation API over Postgres.
- The open-source build is artificially incomplete: invoicing, audit log and
  billing live in **private extension repositories**
  (`extensions/manifest.json` pins `extension-billing`, `extension-services`,
  `extension-invoicing`, `extension-auditing`). They cannot be unlocked —
  they do not exist here. They must be written.
- A single static binary + Postgres is a materially better self-hosting story
  than PHP-FPM/Octane + queue workers + scheduler + extensions.

## What "all features open source" means

The OSS codebase already unlocks plan gating: `app/Service/BillingContract.php`
is a stub returning `hasSubscription() === true`, unlimited goals, billable
rates enabled. The cloud gates via the private `extension-billing`. So:

| Feature | Status in upstream OSS | In tock |
|---|---|---|
| Time tracking, calendar, timesheet, breaks | present | port |
| Reporting + CSV/XLSX/ODS export | present | port |
| Shared/public reports | present | port |
| Goals | present, unlimited via stub | port, no limit |
| Billable hours & rates | present | port |
| Estimates/budgets, rounding | present | port |
| Teams, roles, project/org access | present | port |
| Importers (Toggl, Clockify, Harvest, generic) | present | port |
| Audit log | infra present (`audits` table, `Audit` model, Filament resource) | port + native viewer UI |
| **Invoicing** | **absent — private extension** | **build from scratch** |
| Cloud billing (Stripe) | private extension | **do not build** — everything free |
| Extension/module system | `nwidart/laravel-modules` | **drop** — compiled binary, YAGNI |
| Filament admin panel | present | **drop**; minimal admin endpoints only if needed |

Non-negotiable: **no feature gates, no license checks, no plan checks** in the
Go codebase. The capability matrix is the only authorization model.

## Architecture (locked)

| Layer | Choice | Notes |
|---|---|---|
| Language | Go 1.24+ | module `github.com/Dvorinka/tock` |
| HTTP | `chi` or `gin` — pick `chi` unless a strong reason appears | stdlib-adjacent, no magic |
| DB | PostgreSQL 16+, `pgx/v5` + `sqlc` | schema stays **identical** to upstream |
| Migrations | `goose`, SQL files in `db/migrations/` | see "Schema strategy" |
| Queue/jobs | Postgres table + in-process worker loop (`FOR UPDATE SKIP LOCKED`) | same binary, `tock serve` runs workers |
| Cache/sessions | Postgres first; Dragonfly only if a measured need appears | no Redis dependency |
| Auth (web) | Cookie session, own implementation (Fortify-equivalent: login, register, email verify, password reset, TOTP 2FA, session management) | `password_hash` compat: keep bcrypt/argon2id verification compatible with existing hashes |
| Auth (API) | Laravel Passport-equivalent OAuth2 server | see "OAuth2 compatibility" |
| Frontend | Existing Vue 3 SPA, **untouched** | see "Frontend strategy" |
| PDF | Gotenberg (external service, unchanged) | same dependency as upstream |
| Excel/CSV | `xuri/excelize`, stdlib `encoding/csv` | ODS: small zip+XML writer or defer |
| Mail | SMTP via `gomail`-style lib | mailpit in dev |
| Storage | Local disk + S3-compatible (`minio` in dev) | flysystem equivalent |
| Observability | `slog` JSON logs; OTEL on demand | — |
| Packaging | Single `tock` binary (`serve`, `migrate`, `worker` subcommands) + one Docker image | SPA embedded via `go:embed` |

### Repo layout

```
tock/
  cmd/tock/            # single binary: serve / migrate / worker
  internal/
    config/  db/  httpapi/  auth/  oauth2/  domain/  jobs/  mail/  storage/  import/  export/  invoice/  audit/
  db/migrations/       # goose .sql — baseline + forward
  db/queries/          # sqlc
  resources/js/        # upstream Vue app — stays here, built by Vite, embedded
  openapi.json         # upstream contract snapshot — parity harness input
  app/  config/  routes/  database/   # PHP reference — delete at parity milestone
  PLAN.md  AGENTS.md
```

## The three hard problems

### 1. OAuth2 / Passport compatibility

The desktop app, browser extension and third-party API users authenticate
against Passport endpoints (`/oauth/authorize`, `/oauth/token`,
`/oauth/tokens`, personal access tokens under `/api/v1/users/me/api-tokens`).

Requirement: **stock solidtime-desktop must pair with a tock server without
modification.** That means matching endpoint paths, grant types (authorization
code + PKCE, refresh token), token response shape, scopes, and the `auth:api`
bearer-token validation semantics.

Implementation: hand-rolled OAuth2 authorization server over Postgres tables
(`oauth_clients`, `oauth_auth_codes`, `oauth_access_tokens`,
`oauth_refresh_tokens` — upstream tables already exist via Passport
migrations). Opaque tokens hashed in DB exactly as Passport does (the access
token column stores hashes — verify against
`app/Models/Passport/*` and Passport source). Do not pull in a heavy OAuth
framework; the surface is ~6 endpoints.

Parity check: run upstream locally (`docker compose up`), walk the desktop
app's real auth flow against it with `mitmproxy`, record requests, replay
against tock.

### 2. Inertia protocol

The Vue app uses Inertia for page shells (`routes/web.php` returns
`Inertia::render(...)`) and Ziggy's `route()` helper. Data flows over
`/api/v1` via a generated TanStack client — Inertia carries only initial
props and navigation.

Options, in preference order:

1. **Minimal Inertia server adapter in Go** (~300 LOC): `X-Inertia` header →
   JSON `{component, props, url, version}`; otherwise HTML with `data-page`.
   Asset version hash, shared props (auth user, flash, errors), 409-conflict
   redirect on version mismatch. Also emit a Ziggy-compatible routes manifest
   endpoint so `route()` keeps working. Frontend stays 100% untouched.
2. Drop Inertia → vue-router: cleaner long term, but touches routing guards,
   page props and 431 components' mount contracts. Defer unless the adapter
   proves fragile.

### 3. Proprietary features to build from scratch

**Invoicing** (spec from solidtime.io/features/invoicing):
- Invoices: `draft → sent → paid` lifecycle, duplicate, delete; number series
  (`INV-YYYY-NNN` configurable).
- Line items generated from selected billable time entries; per-project/member
  rates applied; manual lines allowed.
- VAT/tax rates (default per company, per-invoice override), fixed/percentage
  discounts, EU reverse-charge flag.
- Company templates: seller details, VAT ID, default rate, notes, footer,
  logo.
- PDF render via Gotenberg (HTML template → PDF), attachable "detailed hourly
  report" (reuse reporting aggregation as PDF appendix).
- EN 16931 e-invoice (UBL XML) — own milestone, `stretch`.
- Storage of PDFs + XML on the storage disk; email-send later (SMTP) — v1 can
  be download-only.

**Audit log**: `audits` table + `owen-it/laravel-auditing` infra exists in OSS
(created 2024-09 migration + 2026-10 owner columns). Port semantics: audit
every mutating domain operation (old/new values, user, IP via
`CustomIpAddressResolver` equivalent, url, tags). Viewer: dedicated SPA
section (the Filament `AuditResource` shows fields — replicate its columns in
a Vue page). No Filament port.

## Phased roadmap

Each phase ends green: `go build ./...`, `go test ./...`,
`golangci-lint run`, and the phase's parity checklist.

### Phase 0 — Bootstrap & schema

- `go mod init`, `cmd/tock` skeleton: `serve`, `migrate`, `version`.
- Dev `docker-compose.yml`: pgsql, pgsql_test, gotenberg, mailpit, minio.
- **Baseline schema**: run upstream `php artisan migrate` on a scratch
  Postgres, `pg_dump --schema-only`, convert to
  `db/migrations/00001_baseline.sql`. All future changes are new goose
  migrations — never edit applied ones.
- sqlc codegen wired; `justfile` recipes (`just dev`, `just test`,
  `just seed`).
- CI: build, lint (`golangci-lint`), test.
- Seeder porting upstream `Database\Seeders` demo data so the SPA is usable.

### Phase 1 — Identity & auth

- Web: register, login, logout, email verification, password reset,
  TOTP 2FA, "other browser sessions" logout. Session table, cookie flags
  matching upstream expectations.
- OAuth2 server per §1 + personal access tokens API.
- `GET /api/v1/users/me`, `/users/me/memberships`,
  `/users/me/current-organization`, user update/delete,
  resend-verification, reset-pending-email, verify-email-change signed URL.
- Inertia adapter (option 1) + Ziggy manifest + shared props.
- Milestone: sign into the real Vue app served by tock.

### Phase 2 — Organization domain

- organizations CRUD; members (roles: owner/admin/manager/employee/
  placeholder — port `PermissionStore` capability matrix verbatim);
  invitations + signed accept URLs; placeholder ↔ member transitions
  (`make-placeholder`, `invite-placeholder`, `merge-into`); multi-org switch.
- clients, projects (color, rates, estimates/budgets, `is_archived`),
  project-members, tasks, tags.
- Billable rates everywhere (member, project-member, project, org default)
  — port `BillableRateService` resolution order exactly.

### Phase 3 — Time entries & timer

- time-entries: index (filters), store, update, `PATCH` bulk update,
  delete, bulk delete, `users/me/time-entries/active`, calendar range
  queries, timesheet grid data.
- Running-timer semantics: single active entry per member, `start`/`end`
  nullability, timezone-aware day grouping — port `TimeEntryService`,
  `IntervalService`, `TimeEntryFilter`.
- Dashboard charts: all 9 `charts/*` endpoints.
- Breaks (time-entry break fields) if present in schema — verify.

### Phase 4 — Reporting & sharing

- Aggregation engine: port `TimeEntryAggregationService` → SQL
  (`date_trunc`, window functions, grouped rollups by
  project/task/client/user/tag/billable/week/day/hour).
- Reports CRUD (saved report definitions), public shared-report endpoint
  + `/shared-report` page.
- Export: CSV, XLSX, (ODS), PDF-via-Gotenberg for aggregated + detailed.
- Rounding rules on export/display — port exact upstream semantics.

### Phase 5 — Goals

- goals CRUD; progress engine (`GoalProgressService`): at-least/less-than
  targets per day/week/month, filter scopes (projects, clients, tasks, tags
  contains/not-contains, billable/work/breaks), live progress including
  running timer, achieved/in-progress/on-track/exceeded states. No limit.

### Phase 6 — Import & export

- Importers: port `ImporterContract` + Toggl, Clockify, Harvest, generic
  CSV, and solidtime-export importers (zip handling via
  `spatie/temporary-directory` equivalent).
- Full-organization export endpoint.
- Jobs table + worker for long-running imports; progress reporting
  (poll endpoint — check upstream mechanism).

### Phase 7 — Invoicing (net-new)

- Schema: `invoices`, `invoice_items`, `invoice_templates` (+ numbering
  counters). goose migrations, tock-owned tables.
- REST endpoints under `/api/v1/organizations/{org}/invoices*` +
  `/invoice-templates*`.
- Select-billable-time → line items flow; VAT, discount, reverse charge;
  draft→sent→paid; duplicate.
- PDF via Gotenberg; attach detailed hourly report.
- SPA section (new Vue pages, existing component conventions).
- Stretch: EN 16931 UBL e-invoice export.

### Phase 8 — Audit log

- Audit write-path hooks on every mutating domain op (wrap the domain
  services — no ORM magic needed since sqlc is explicit).
- `GET /api/v1/organizations/{org}/audits` (filters: user, model, event,
  date) + SPA viewer replicating the Filament resource columns.

### Phase 9 — Ship

- Delete `app/`, `routes/`, `database/` PHP, `composer.*`, `artisan`,
  `config/` PHP.
- Rebrand strings "solidtime" → "tock" (keep DB/table names identical for
  migration compat — brand is cosmetic only).
- Dockerfile: single image, binary + embedded SPA + migrations on boot.
- `docs/migration-from-solidtime.md`: point tock at an existing solidtime
  Postgres → verify all data intact (drop-in parity requirement).
- Release pipeline: goreleaser, versioned image tags.

## Parity & verification harness

- `openapi.json` (upstream snapshot, committed at repo root) is the API
  contract. Generate tock's spec (or hand-maintain) and **diff** — every
  endpoint, field and error shape must match before "parity" is claimed.
- Upstream `tests/` (146 PHP files): port the feature/API-level ones to Go
  integration tests hitting real Postgres. Unit-test the domain services;
  treat the PHP tests as executable spec.
- Upstream `e2e/` Playwright tests run against the real UI — keep them
  working against tock.
- Reference oracle: `docker compose up` upstream on a side port during
  development; send identical requests to both, diff responses
  (exclude volatile fields: ids, timestamps).
- **Data compatibility gate**: restore a real upstream `pg_dump` into tock's
  Postgres; every feature must work on legacy rows (password hashes, token
  hashes, encrypted columns — check for Laravel-encrypted casts in models).

## Frontend strategy

- Keep `resources/js` as-is. Vite build → `dist`/`build` output embedded in
  the binary via `go:embed` (dev mode proxies to Vite).
- Inertia adapter serves page shells + props (Phase 1).
- Ziggy manifest endpoint generated from tock's route table.
- The two web controllers with real logic to port: `DashboardController`,
  `OrganizationController` (web show/create), `UserProfileController`,
  `OrganizationInvitationController` (signed accept), `OtherBrowserSessions`,
  `Web\UserController` (email-change verify).
- New surfaces added (invoice editor, audit viewer) follow upstream
  `Components/` + `packages/ui` conventions — the design system exists; use
  it.

## Explicitly out of scope

- Stripe/cloud billing, subscriptions, trials — nothing gated, ever.
- Laravel module/extension system — single binary, no runtime plugins.
- Filament admin panel — no port; admin ops via CLI flags if ever needed.
- Octane/FrankenPHP, queue workers as separate processes, Laravel scheduler
  (cron becomes an in-process ticker for periodic jobs like report recalc —
  verify upstream `Console/` for the actual scheduled tasks before
  implementing).
- Mobile app, desktop app, browser extension — untouched; they consume the
  API and must work unmodified.

## Legal / housekeeping

- AGPL-3.0 retained (fork requirement). Keep `LICENSE.md`; new code headers
  per repo convention.
- "solidtime" is upstream's brand — public-facing strings migrate to "tock";
  never market under their name. DB identifiers stay for compat (not branding).
- Keep `upstream` remote; periodic `git fetch upstream` to mine fixes for the
  reference behavior until PHP is deleted.
- Private extension repos stay private — do not attempt to access or copy
  them. Invoicing is reimplemented from the public feature spec + API docs,
  not leaked code.

## Definition of done (parity)

1. Every route in upstream `routes/api.php` + `routes/web.php` answered by
   tock with matching contract (openapi diff clean).
2. Stock solidtime-desktop completes OAuth pairing and syncs against tock.
3. Fresh upstream DB dump boots on tock: login works with existing hashes,
   timers, reports, goals, exports all correct.
4. Invoicing + audit log functional with no license machinery anywhere.
5. `docker run` one container: Postgres external, everything else inside.
6. e2e Playwright suite green.

---

## Next-agent kickoff prompt

Paste into a fresh agent on this repo (Orca → tock → new workspace):

```text
You are building "tock": a Go rewrite of the solidtime time-tracking backend.
This repo is a fork of solidtime-io/solidtime; the PHP app in app/, routes/,
config/, database/ is the executable spec — port behavior, don't redesign.

Read PLAN.md fully first — it is the contract. Then read:
- routes/api.php, routes/web.php (full endpoint surface)
- app/Service/TimeEntryService.php, TimeEntryAggregationService.php,
  BillableRateService.php, PermissionStore.php (core domain rules)
- database/migrations/ (schema), resources/js/utils/use*Query.ts
  (what the frontend actually calls)
- openapi.json (API contract for parity diffing)

Locked stack: Go + chi, Postgres + pgx/v5 + sqlc, goose SQL migrations in
db/migrations/, single binary (cmd/tock: serve/migrate/worker), Postgres as
job queue (FOR UPDATE SKIP LOCKED), Gotenberg for PDF, slog JSON logging.
Frontend stays the upstream Vue app — served via go:embed; you will implement
a minimal Inertia server adapter, do NOT rewrite the frontend.

Hard rules:
- No feature gates, plan checks, or license code. Every feature ships to
  everyone (that's the point of the fork).
- Schema/table/column names identical to upstream — a real upstream pg_dump
  must restore and work unchanged.
- OAuth2 endpoints must let stock solidtime-desktop pair unmodified.
- Errors: wrapped fmt.Errorf at edges; typed errors in domain pkgs.
- Conventional commits (feat(api): …), branch + PR, never push to main
  directly. Commits carry: Authored By: TDvorak <info@tdvorak.dev>
- go build ./... && go test ./... && golangci-lint run must be green before
  every push. Verify behavior against upstream running in docker compose as
  the oracle.

Start with Phase 0 of PLAN.md: Go module skeleton, baseline goose migration
from `pg_dump --schema-only` of a migrated upstream DB, dev docker-compose
(pgsql, pgsql_test, gotenberg, mailpit, minio), sqlc codegen, justfile,
CI workflow. Deliver `tock serve` answering /api/v1/users/me with a 401 and
the SPA's built assets served at /. Open a PR when Phase 0 is green.
```
