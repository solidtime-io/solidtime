<?php

declare(strict_types=1);

use App\Extensions\Scramble\ApiExceptionTypeToSchema;
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * Your API path. By default, all routes starting with this path will be added to the docs.
     * If you need to change this behavior, you can add your custom routes resolver using `Scramble::routes()`.
     */
    'api_path' => 'api',

    /*
     * Your API domain. By default, app domain is used. This is also a part of the default API routes
     * matcher, so when implementing your own, make sure you use this config if needed.
     */
    'api_domain' => null,

    'info' => [
        /*
         * API version.
         */
        'version' => '0.0.1',

        /*
         * Description rendered on the home page of the API documentation (`/docs/api`).
         */
        'description' => <<<'MD'
## Getting started

All organization endpoints live under `/v1/organizations/{organization}`, where `{organization}` is the organization's ID. Authenticate with `Authorization: Bearer <token>` and send `Accept: application/json`.

**1. Find yourself.** Call `GET /v1/users/me/memberships`. Each membership contains the **organization ID** (use it as `{organization}` in paths) and your **member ID** in that organization (the membership `id`). Most endpoints filter by member ID, not by user ID.

**2. Scope to your own data.** For owners and admins, `GET /time-entries` and `GET /time-entries/aggregate` return the whole organization's time entries unless you pass `member_id`. When acting for "me", always pass your member ID, both when reading and before changing entries.

**3. Resolve names to IDs.** Look up projects, clients, tags, tasks and members by name with their list endpoints (`GET /projects`, `GET /clients`, `GET /tags`, `GET /tasks`, `GET /members`). Never guess IDs.

**4. Use UTC.** All timestamps are sent and returned in UTC as `Y-m-d\TH:i:s\Z` (example: `2026-10-02T07:30:00Z`). Convert the user's local times and day boundaries to UTC before sending them.

**5. Money is in cents.** Billable rates and costs are integers in cents of the organization's currency (`8000` means 80.00).

## Common tasks

- **Start a timer:** stop the running entry first (find it with `GET /v1/users/me/time-entries/active`, then `PUT` its `end`), then `POST /time-entries` with `start` and `end: null`. Only one entry can run per member.
- **Log past work:** `POST /time-entries` once per block with `member_id`, `start`, `end`, `project_id` and `billable` set to the project's `is_billable` (it is not derived automatically).
- **Fix or stop an entry:** `PUT /time-entries/{timeEntry}` with the new `start` or `end` in UTC.
- **Move entries to another project:** list them with `member_id` and filters, then `PATCH /time-entries` with their `ids` and `changes.project_id`, plus `changes.task_id` set to a task of the new project or `null`.
- **Totals and reports:** `GET /time-entries/aggregate` with `group` (for example `client` or `project`) and `start`/`end`; durations are in `seconds`, amounts in `cost` (cents).
- **Share a report:** `POST /reports` with `is_public: true` and use the returned `shareable_link`.
MD,
    ],

    /*
     * Customize Stoplight Elements UI
     */
    'ui' => [
        /*
         * Hide the `Try It` feature. Enabled by default.
         */
        'hide_try_it' => false,

        /*
         * URL to an image that displays as a small square logo next to the title, above the table of contents.
         */
        'logo' => '',

        /*
         * Use to fetch the credential policy for the Try It feature. Options are: omit, include (default), and same-origin
         */
        'try_it_credentials_policy' => 'include',
    ],

    /*
     * The list of servers of the API. By default, when `null`, server URL will be created from
     * `scramble.api_path` and `scramble.api_domain` config variables. When providing an array, you
     * will need to specify the local server URL manually (if needed).
     *
     * Example of non-default config (final URLs are generated using Laravel `url` helper):
     *
     * ```php
     * 'servers' => [
     *     'Live' => 'api',
     *     'Prod' => 'https://scramble.dedoc.co/api',
     * ],
     * ```
     */
    'servers' => [
        'Production' => 'https://app.solidtime.io/api',
        'Staging' => 'https://app.staging.solidtime.io/api',
        'Local' => 'https://solidtime.test/api',
    ],

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [
        ApiExceptionTypeToSchema::class,
    ],
];
