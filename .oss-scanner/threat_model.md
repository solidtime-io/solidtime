# Threat model

## What this project does

solidtime is an open-source, multi-tenant time tracking web application (Laravel backend, Vue 3 + Inertia frontend,
PostgreSQL). It runs as a hosted SaaS (solidtime.io) and is self-hosted by many organisations. Users belong to one or
more **organizations**; inside an organization each member has a role: `owner`, `admin`, `manager`, `employee` or
`placeholder` (an imported, non-login member). What each role may do is defined in `app/Service/PermissionStore.php`.

The most important security property is **isolation**: a user must never read or modify data of an organization they
are not a member of, and within an organization a member must not exceed the permissions of their role (e.g. an
employee must not see other members' time entries, billable rates, or manage members, unless the organization settings
explicitly allow it).

## Trust boundaries

- **Super admins are fully trusted.** They are the instance operators, configured via the `SUPER_ADMINS` env
  variable, and have access to the Filament admin panel (`app/Filament`), which can view and change data of every
  organization and impersonate users. Anything a super admin can do through the panel (including XSS, SQL injection,
  SSRF or file access that is only reachable from the panel) is not a vulnerability.
- What **is** in scope: a user who is not a super admin reaching the admin panel, or any of its actions, at all.
- Operators of a self-hosted instance (shell, database, environment, filesystem access) are trusted.
- Everyone else, including organization owners and admins when acting outside their own organization, is untrusted.

## Runtime: long-lived Octane workers

In production (the hosted SaaS and the official Docker image, `docker/prod/`) the app does **not** run as one PHP
process per request. It runs on Laravel Octane with FrankenPHP in worker mode: each worker boots the application once
and then serves many requests from different users and organizations. Anything kept in memory survives from one request
to the next unless Octane resets it (`config/octane.php` lists what is reset). This includes static properties and
static caches, container bindings registered with `singleton()` instead of `scoped()` (see
`app/Providers/AppServiceProvider.php`), objects captured by those singletons, runtime `config()` / locale / timezone
changes, macros and event listeners registered during a request, and state in third-party packages.

Request A leaving state behind that request B (another user, possibly of another organization) then sees or is
affected by is in scope, rated by its impact like any other issue (see severity below). The PHPUnit suite cannot
show this class of bug, because it boots a fresh application for every test. It has to be reproduced over HTTP against
the Octane server, see "How to exercise it".

## Where untrusted input enters

All authenticated users, including employees of any organization and anyone who self-registers (registration is open
by default), are untrusted.

- **JSON API** `routes/api.php` (`/api/v1/...`), authenticated via Passport (session cookie or personal access token).
  Most routes are scoped by `{organization}` and authorised in the controllers / form requests.
- **Public, unauthenticated** endpoints: `GET /api/v1/public/reports` (shared reports, accessed by a secret), login,
  registration, password reset, email verification, organization invitation acceptance (`routes/web.php`).
- **Web / Inertia routes** `routes/web.php` and Fortify/Jetstream actions in `app/Actions`.
- **Imports** (`app/Service/Import/Importers`): user-uploaded CSV and ZIP files from Toggl, Clockify, Harvest,
  generic CSV and solidtime's own export format. ZIP handling is in `ZipImportHelper.php`.
- **Exports / reports** (`app/Service/Export`, `app/Service/ReportExport`): CSV/XLSX/ODS and PDF. PDFs are rendered by
  sending HTML to a Gotenberg (headless Chromium) service, so user-controlled content in that HTML matters.
- **OAuth** (Passport) authorization and token endpoints.
- **Filament admin panel** (`app/Filament`): only its access control is in scope (see Trust boundaries).

## Components that matter most / least

Most important: organization scoping and role checks in the API controllers, form requests (`app/Http/Requests`),
`PermissionStore`, public report sharing, invitations and member management (role changes, ownership transfer, member
merge), authentication flows (Fortify, 2FA, email change, API tokens), import parsing.

Less important / out of scope:
- `extensions/` is empty in this repository (proprietary modules are not part of the open-source code).
- `docker/`, `k8s/`, `e2e/`, `playwright/`, `docs/` and developer tooling.
- Third-party dependencies in `vendor/` and `node_modules/`, unless solidtime uses them in an unsafe way.

## How to exercise it

- `.oss-scanner/start-postgres.sh` starts the local PostgreSQL server (user `root`, password `root`, db `laravel`).
- `.oss-scanner/start-gotenberg.sh` starts the local Gotenberg server (PDF rendering via headless Chromium) on
  http://127.0.0.1:3000, so the PDF export code path, including what Chromium does with the rendered HTML, can be
  exercised.
- `php artisan test` runs the PHPUnit suite (start PostgreSQL and Gotenberg first); all tests are expected to pass. Endpoint tests in `tests/Unit/Endpoint/Api/V1/` show how to create users,
  organizations and members with factories and call the API with a given role; they are the quickest way to write a
  reproducer. Example: `php artisan test --filter=TimeEntryEndpointTest`.
- To run the app like production: `.oss-scanner/start-octane.sh` (http://127.0.0.1:8000). It starts PostgreSQL and
  Gotenberg, migrates (and seeds an empty database, see `database/seeders/DatabaseSeeder.php` for the users) and runs
  Octane/FrankenPHP with the production Caddyfile and a **single worker**, so consecutive requests always hit the same
  worker and leaks between requests reproduce reliably. Workers keep the code they booted with: after changing PHP
  code run `php artisan octane:reload`. Stop it with `php artisan octane:stop`. Note that the test suite and the app
  share the same database, so `php artisan test` wipes the app's data.
- A reproducer for a leak between requests is a script that sends request A (e.g. as a member of organization X) and
  then request B (as a user of organization Y) to the running Octane server and shows that B observes A's state. API
  requests can be authenticated with a personal access token, e.g. created with
  `php artisan tinker --execute="echo App\Models\User::where('email', '...')->first()->createToken('t')->accessToken;"`.
- There is no network: mail delivery is not available (mail uses the `array` driver in tests), and remote resources
  referenced by PDF templates (e.g. fonts from fonts.bunny.net) fail to load, so PDFs fall back to local fonts.

## How we rate severity

- **Critical**: unauthenticated access to other users' data or accounts; authentication bypass; remote code execution;
  SQL injection reachable by any registered user; reading or writing data of an organization the attacker is not a
  member of, including through state leaking between requests in an Octane worker.
- **High**: privilege escalation within an organization (e.g. employee to admin/owner, or performing admin-only
  actions); access to data the role must not see (other members' time entries, billable rates, member emails) when
  the organization settings do not allow it; stored XSS that executes in another user's session; SSRF via PDF
  rendering or imports; account takeover requiring user interaction.
- **Medium**: information disclosure with limited impact, CSRF on state-changing endpoints, issues requiring an
  unusual but realistic configuration, denial of service by a single authenticated request (e.g. pathological
  import file).
- **Low**: everything else with real security impact.

## Anything to leave alone

Please do not report (see also `SECURITY.md`):
- Theoretical findings without a working reproducer.
- Missing or weak security headers in isolation; TLS / mail DNS configuration.
- Self-XSS; CSRF on non-state-changing endpoints (logout, theme).
- CSV / spreadsheet formula injection in exports.
- Owners or admins acting destructively within their own organization.
- Anything requiring direct DB, shell or filesystem access on a self-hosted instance.
- Anything that requires being a super admin, including issues inside the Filament admin panel.
- Missing OAuth scope enforcement (not implemented yet).
- Rate-limit tuning and generic DoS through volume of requests.

## Reports and patches

Please include the affected endpoint or code path, the attacker's role and the victim, a PHPUnit test (in the style of
`tests/Unit/Endpoint/Api/V1/`) that reproduces the issue (for leaks between requests: a script against
`.oss-scanner/start-octane.sh` instead), and a minimal patch that follows the existing patterns
(authorisation in form requests/controllers via `PermissionStore`).
