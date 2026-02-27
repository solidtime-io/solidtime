# Linear Integration Design

## Overview

One-way sync from Linear to solidtime. Linear issues assigned to the authenticated user are synced as solidtime Tasks. Built as an extension module in `extensions/Linear/`.

## Decisions

- **Sync direction**: Linear → solidtime only
- **Entity mapping**: Linear Issues → solidtime Tasks, Linear Projects → solidtime Projects
- **Sync mechanism**: Webhooks (real-time) + polling every 15 min (catch-up safety net)
- **Sync scope**: Only issues assigned to the connected user
- **API client**: Thin custom GraphQL client using Guzzle (no community SDK)
- **Architecture**: Extension module in `extensions/Linear/`

## Extension Structure

```
extensions/Linear/
├── src/
│   ├── LinearServiceProvider.php
│   ├── Services/
│   │   ├── LinearGraphQLClient.php
│   │   └── LinearSyncService.php
│   ├── Controllers/
│   │   └── LinearWebhookController.php
│   ├── Jobs/
│   │   ├── SyncLinearIssuesForUser.php
│   │   └── ProcessLinearWebhook.php
│   ├── Models/
│   │   └── LinearIntegration.php
│   └── Console/
│       └── SyncLinearCommand.php
├── database/migrations/
│   ├── create_linear_integrations_table.php
│   └── add_linear_id_to_tasks_table.php
├── routes/api.php
├── resources/js/Pages/
├── vite.config.js
├── composer.json
└── tests/
```

## Data Model

### New table: `linear_integrations`

| Column | Type | Description |
|--------|------|-------------|
| id | UUID PK | Primary key |
| user_id | UUID FK | solidtime user |
| organization_id | UUID FK | solidtime org |
| access_token | text (encrypted) | Linear OAuth access token |
| refresh_token | text (encrypted) | Linear OAuth refresh token |
| token_expires_at | timestamp | 24h expiry, triggers refresh |
| linear_user_id | string | Linear user ID for filtering |
| webhook_secret | string (encrypted) | HMAC signing secret |
| last_synced_at | timestamp | Watermark for polling catch-up |
| created_at/updated_at | timestamps | Standard |

### Schema changes

- `tasks.linear_id` — nullable string, unique per `organization_id`
- `projects.linear_project_id` — nullable string, unique per `organization_id`

## OAuth Flow

1. User clicks "Connect Linear" → redirect to `https://linear.app/oauth/authorize` with `read` scope
2. Linear redirects back with auth code → exchange for access + refresh tokens
3. Fetch authenticated Linear user ID, store in `linear_integrations`
4. Register webhook via `webhookCreate` mutation for `Issue` events on all public teams

## Sync Logic

### Webhook processing (`ProcessLinearWebhook` job)

1. Verify HMAC-SHA256 signature + check `webhookTimestamp` within 60 seconds
2. Return 200 immediately, dispatch job for async processing
3. On `create`/`update`: if `assignee.id` matches a connected user, upsert task by `linear_id`
4. On `remove`: leave task as-is (don't delete tracked time)
5. If issue unassigned from user, optionally archive the task

### Polling catch-up (`SyncLinearCommand`, every 15 min)

1. For each integration, refresh token if near expiry
2. Query `issues(filter: { assignee: { id: { eq: $id } }, updatedAt: { gt: $lastSyncedAt } }, orderBy: updatedAt)`
3. Page through results, upsert tasks
4. Update `last_synced_at` watermark

### Field mapping

| Linear | solidtime |
|--------|-----------|
| `id` | `tasks.linear_id` |
| `title` | `tasks.name` |
| `project.id` | resolves to Project via `projects.linear_project_id` |
| `state.type` == completed/canceled | `tasks.done_at` |
| `estimate` | `tasks.estimated_time` |

## API Routes

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/api/v1/organizations/{org}/linear/connect` | Initiate OAuth |
| GET | `/api/v1/organizations/{org}/linear/callback` | OAuth callback |
| POST | `/api/v1/linear/webhook` | Webhook receiver (signature-verified, no auth) |
| GET | `/api/v1/organizations/{org}/linear/status` | Connection status |
| DELETE | `/api/v1/organizations/{org}/linear/disconnect` | Remove integration |

## Settings UI

Minimal page in org settings:
- Connection status + Linear username
- Connect/Disconnect buttons
- Last synced timestamp
- Manual "Sync now" button

## Error Handling

- **Token revocation**: Mark integration as disconnected, surface notification in settings UI
- **Webhook signature failure**: Return 401, log attempt
- **Unrecognized user in webhook**: Ignore silently (200 response)
- **Rate limiting**: Back off, retry on next scheduled run (5,000 req/hr is generous)
- **Duplicates**: `linear_id` unique constraint prevents double-creates
- **Deleted issues in Linear**: Leave task as-is, preserve tracked time
- **Archived issues in Linear**: Set `done_at` on the task
- **Multiple orgs**: Each org gets its own synced tasks, mirrors solidtime's data isolation
