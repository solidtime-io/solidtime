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
- **Filament admin panel** (`app/Filament`), only for instance super admins (`SUPER_ADMINS` env). Super admins are
  trusted.

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
- `php artisan test` runs the PHPUnit suite. Endpoint tests in `tests/Unit/Endpoint/Api/V1/` show how to create users,
  organizations and members with factories and call the API with a given role; they are the quickest way to write a
  reproducer. Example: `php artisan test --filter=TimeEntryEndpointTest`.
- To run the app: `php artisan migrate:fresh --seed && php artisan serve` (http://127.0.0.1:8000). Note that the
  test suite and the app share the same database.
- There is no network: Gotenberg (PDF generation) and mail delivery are not available. Mail uses the `array`
  driver in tests. The 8 PDF export tests in `TimeEntryEndpointTest` fail for this reason; that is expected.

## How we rate severity

- **Critical**: unauthenticated access to other users' data or accounts; authentication bypass; remote code execution;
  SQL injection reachable by any registered user; reading or writing data of an organization the attacker is not a
  member of.
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
- Anything requiring direct DB, shell or filesystem access on a self-hosted instance, or super admin access.
- Missing OAuth scope enforcement (not implemented yet).
- Rate-limit tuning and generic DoS through volume of requests.

## Reports and patches

Please include the affected endpoint or code path, the attacker's role and the victim, a PHPUnit test (in the style of
`tests/Unit/Endpoint/Api/V1/`) that reproduces the issue, and a minimal patch that follows the existing patterns
(authorisation in form requests/controllers via `PermissionStore`).
