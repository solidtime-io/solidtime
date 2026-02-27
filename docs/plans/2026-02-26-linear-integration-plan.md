# Linear Integration Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a solidtime extension that syncs Linear issues (assigned to the user) as Tasks via OAuth, webhooks, and polling.

**Architecture:** Extension module in `extensions/Linear/` using nwidart/laravel-modules. Thin Guzzle-based GraphQL client talks to Linear's API. Webhooks provide real-time sync, scheduled polling (every 15 min) catches missed events.

**Tech Stack:** PHP 8.3, Laravel 12, Guzzle HTTP, nwidart/laravel-modules, PostgreSQL, Vue 3 + Inertia.js

**Design doc:** `docs/plans/2026-02-26-linear-integration-design.md`

---

### Task 1: Scaffold the Extension Module

**Files:**
- Create: `extensions/Linear/composer.json`
- Create: `extensions/Linear/app/Providers/LinearServiceProvider.php`
- Create: `extensions/Linear/routes/api.php`
- Create: `extensions/Linear/vite.config.js`
- Modify: `modules_statuses.json` (create if not exists)

**Step 1: Create extension composer.json**

```json
{
    "name": "solidtime-io/linear",
    "description": "Linear integration for solidtime",
    "type": "library",
    "require": {},
    "autoload": {
        "psr-4": {
            "Extensions\\Linear\\": "app/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Extensions\\Linear\\Providers\\LinearServiceProvider"
            ]
        }
    }
}
```

**Step 2: Create the service provider**

Reference pattern: `config/modules.php` — module namespace is `Extensions`, app folder is `app/`.

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Providers;

use Illuminate\Support\ServiceProvider;

class LinearServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function register(): void
    {
        //
    }
}
```

**Step 3: Create empty routes file**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Linear integration routes will be registered here
```

**Step 4: Create vite.config.js**

```javascript
export const paths = [];
export const plugins = [];
```

**Step 5: Create/update modules_statuses.json**

```json
{
    "Linear": true
}
```

**Step 6: Run composer dump-autoload and verify module loads**

Run: `cd /Users/joelmgallant/git/github/solidtime && composer dump-autoload`
Then: `php artisan module:list` (or `php artisan route:list | grep linear` to verify no errors)

**Step 7: Commit**

```bash
git add extensions/Linear/ modules_statuses.json
git commit -m "feat(linear): scaffold extension module"
```

---

### Task 2: Database Migrations

**Files:**
- Create: `extensions/Linear/database/migrations/2026_02_26_000001_create_linear_integrations_table.php`
- Create: `extensions/Linear/database/migrations/2026_02_26_000002_add_linear_id_to_tasks_table.php`
- Create: `extensions/Linear/database/migrations/2026_02_26_000003_add_linear_project_id_to_projects_table.php`

**Step 1: Create linear_integrations migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('linear_integrations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('organization_id');
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('token_expires_at');
            $table->string('linear_user_id');
            $table->text('webhook_secret')->nullable();
            $table->string('webhook_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['user_id', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('linear_integrations');
    }
};
```

**Step 2: Create tasks linear_id migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->string('linear_id')->nullable()->index();
            $table->unique(['organization_id', 'linear_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropUnique(['organization_id', 'linear_id']);
            $table->dropColumn('linear_id');
        });
    }
};
```

**Step 3: Create projects linear_project_id migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('linear_project_id')->nullable()->index();
            $table->unique(['organization_id', 'linear_project_id']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropUnique(['organization_id', 'linear_project_id']);
            $table->dropColumn('linear_project_id');
        });
    }
};
```

**Step 4: Run migrations to verify**

Run: `php artisan migrate`
Expected: Three migrations run successfully.

**Step 5: Commit**

```bash
git add extensions/Linear/database/
git commit -m "feat(linear): add database migrations"
```

---

### Task 3: LinearIntegration Model

**Files:**
- Create: `extensions/Linear/app/Models/LinearIntegration.php`
- Create: `extensions/Linear/tests/Unit/Models/LinearIntegrationModelTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Unit\Models;

use Extensions\Linear\Models\LinearIntegration;
use App\Models\Organization;
use App\Models\User;
use Tests\TestCaseWithDatabase;

class LinearIntegrationModelTest extends TestCaseWithDatabase
{
    public function test_it_encrypts_tokens(): void
    {
        // Arrange
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        // Act
        $integration = LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'linear-user-123',
        ]);

        // Assert
        $integration->refresh();
        $this->assertEquals('test-access-token', $integration->access_token);
        $this->assertEquals('test-refresh-token', $integration->refresh_token);
        // Raw DB value should be encrypted (not plaintext)
        $raw = \DB::table('linear_integrations')->where('id', $integration->id)->first();
        $this->assertNotEquals('test-access-token', $raw->access_token);
    }

    public function test_it_belongs_to_user_and_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $integration = LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'linear-user-123',
        ]);

        $this->assertTrue($integration->user->is($user));
        $this->assertTrue($integration->organization->is($organization));
    }

    public function test_is_token_expired_returns_true_when_expired(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $integration = LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->subMinutes(10),
            'linear_user_id' => 'linear-user-123',
        ]);

        $this->assertTrue($integration->isTokenExpired());
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test extensions/Linear/tests/Unit/Models/LinearIntegrationModelTest.php`
Expected: FAIL — class not found.

**Step 3: Write the model**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $organization_id
 * @property string $access_token
 * @property string $refresh_token
 * @property Carbon $token_expires_at
 * @property string $linear_user_id
 * @property string|null $webhook_secret
 * @property string|null $webhook_id
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Organization $organization
 */
class LinearIntegration extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'organization_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'linear_user_id',
        'webhook_secret',
        'webhook_id',
        'last_synced_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at->isPast();
    }

    public function isTokenExpiringSoon(): bool
    {
        return $this->token_expires_at->isBefore(now()->addMinutes(5));
    }
}
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test extensions/Linear/tests/Unit/Models/LinearIntegrationModelTest.php`
Expected: PASS (3 tests).

**Step 5: Commit**

```bash
git add extensions/Linear/app/Models/ extensions/Linear/tests/
git commit -m "feat(linear): add LinearIntegration model with encrypted tokens"
```

---

### Task 4: GraphQL Client

**Files:**
- Create: `extensions/Linear/app/Services/LinearGraphQLClient.php`
- Create: `extensions/Linear/tests/Unit/Services/LinearGraphQLClientTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Unit\Services;

use Extensions\Linear\Services\LinearGraphQLClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinearGraphQLClientTest extends TestCase
{
    public function test_query_sends_graphql_request_with_auth_header(): void
    {
        Http::fake([
            'api.linear.app/graphql' => Http::response([
                'data' => ['viewer' => ['id' => 'user-123', 'name' => 'Test User']],
            ], 200),
        ]);

        $client = new LinearGraphQLClient('test-access-token');
        $result = $client->query('{ viewer { id name } }');

        $this->assertEquals('user-123', $result['viewer']['id']);
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'test-access-token')
                && $request->url() === 'https://api.linear.app/graphql';
        });
    }

    public function test_query_with_variables(): void
    {
        Http::fake([
            'api.linear.app/graphql' => Http::response([
                'data' => ['issues' => ['nodes' => []]],
            ], 200),
        ]);

        $client = new LinearGraphQLClient('token');
        $result = $client->query('query ($id: String!) { issues(filter: { assignee: { id: { eq: $id } } }) { nodes { id } } }', ['id' => 'user-1']);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return isset($body['variables']['id']) && $body['variables']['id'] === 'user-1';
        });
    }

    public function test_query_throws_on_graphql_errors(): void
    {
        Http::fake([
            'api.linear.app/graphql' => Http::response([
                'errors' => [['message' => 'Not authorized']],
            ], 200),
        ]);

        $client = new LinearGraphQLClient('bad-token');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not authorized');
        $client->query('{ viewer { id } }');
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test extensions/Linear/tests/Unit/Services/LinearGraphQLClientTest.php`
Expected: FAIL — class not found.

**Step 3: Write the GraphQL client**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class LinearGraphQLClient
{
    private const ENDPOINT = 'https://api.linear.app/graphql';

    public function __construct(
        private readonly string $accessToken,
    ) {}

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public function query(string $query, array $variables = []): array
    {
        $payload = ['query' => $query];
        if ($variables !== []) {
            $payload['variables'] = $variables;
        }

        $response = Http::withHeaders([
            'Authorization' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(self::ENDPOINT, $payload);

        $json = $response->json();

        if (isset($json['errors']) && count($json['errors']) > 0) {
            throw new RuntimeException($json['errors'][0]['message']);
        }

        return $json['data'];
    }
}
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test extensions/Linear/tests/Unit/Services/LinearGraphQLClientTest.php`
Expected: PASS (3 tests).

**Step 5: Commit**

```bash
git add extensions/Linear/app/Services/LinearGraphQLClient.php extensions/Linear/tests/
git commit -m "feat(linear): add thin GraphQL client using Http facade"
```

---

### Task 5: Sync Service

**Files:**
- Create: `extensions/Linear/app/Services/LinearSyncService.php`
- Create: `extensions/Linear/tests/Unit/Services/LinearSyncServiceTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Unit\Services;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Extensions\Linear\Models\LinearIntegration;
use Extensions\Linear\Services\LinearSyncService;
use Tests\TestCaseWithDatabase;

class LinearSyncServiceTest extends TestCaseWithDatabase
{
    public function test_upsert_task_creates_new_task(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();

        $service = new LinearSyncService();
        $service->upsertTask($organization, [
            'id' => 'linear-issue-abc',
            'title' => 'Fix login bug',
            'state' => ['type' => 'started'],
            'project' => null,
            'estimate' => null,
        ]);

        $this->assertDatabaseHas('tasks', [
            'linear_id' => 'linear-issue-abc',
            'name' => 'Fix login bug',
            'organization_id' => $organization->getKey(),
            'done_at' => null,
        ]);
    }

    public function test_upsert_task_updates_existing_task(): void
    {
        $organization = Organization::factory()->create();
        $task = Task::factory()->forOrganization($organization)->create([
            'linear_id' => 'linear-issue-abc',
            'name' => 'Old name',
        ]);

        $service = new LinearSyncService();
        $service->upsertTask($organization, [
            'id' => 'linear-issue-abc',
            'title' => 'New name',
            'state' => ['type' => 'started'],
            'project' => null,
            'estimate' => null,
        ]);

        $task->refresh();
        $this->assertEquals('New name', $task->name);
    }

    public function test_upsert_task_sets_done_at_for_completed_issues(): void
    {
        $organization = Organization::factory()->create();

        $service = new LinearSyncService();
        $service->upsertTask($organization, [
            'id' => 'linear-issue-done',
            'title' => 'Done task',
            'state' => ['type' => 'completed'],
            'project' => null,
            'estimate' => null,
        ]);

        $task = Task::where('linear_id', 'linear-issue-done')->first();
        $this->assertNotNull($task->done_at);
    }

    public function test_upsert_task_links_to_project_by_linear_project_id(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create([
            'linear_project_id' => 'linear-proj-xyz',
        ]);

        $service = new LinearSyncService();
        $service->upsertTask($organization, [
            'id' => 'linear-issue-linked',
            'title' => 'Linked task',
            'state' => ['type' => 'started'],
            'project' => ['id' => 'linear-proj-xyz', 'name' => 'My Project'],
            'estimate' => null,
        ]);

        $task = Task::where('linear_id', 'linear-issue-linked')->first();
        $this->assertEquals($project->getKey(), $task->project_id);
    }

    public function test_upsert_task_creates_project_if_not_exists(): void
    {
        $organization = Organization::factory()->create();

        $service = new LinearSyncService();
        $service->upsertTask($organization, [
            'id' => 'linear-issue-new-proj',
            'title' => 'Task with new project',
            'state' => ['type' => 'unstarted'],
            'project' => ['id' => 'linear-proj-new', 'name' => 'New Linear Project'],
            'estimate' => null,
        ]);

        $this->assertDatabaseHas('projects', [
            'linear_project_id' => 'linear-proj-new',
            'name' => 'New Linear Project',
            'organization_id' => $organization->getKey(),
        ]);

        $task = Task::where('linear_id', 'linear-issue-new-proj')->first();
        $this->assertNotNull($task->project_id);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test extensions/Linear/tests/Unit/Services/LinearSyncServiceTest.php`
Expected: FAIL — class not found.

**Step 3: Write the sync service**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Services;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Carbon;

class LinearSyncService
{
    /**
     * @param  array{id: string, title: string, state: array{type: string}, project: array{id: string, name: string}|null, estimate: float|null}  $issueData
     */
    public function upsertTask(Organization $organization, array $issueData): Task
    {
        $projectId = null;
        if ($issueData['project'] !== null) {
            $project = $this->upsertProject($organization, $issueData['project']);
            $projectId = $project->getKey();
        }

        $doneAt = in_array($issueData['state']['type'], ['completed', 'canceled'], true)
            ? Carbon::now()
            : null;

        $task = Task::where('linear_id', $issueData['id'])
            ->where('organization_id', $organization->getKey())
            ->first();

        if ($task !== null) {
            $task->update([
                'name' => $issueData['title'],
                'project_id' => $projectId ?? $task->project_id,
                'done_at' => $doneAt,
                'estimated_time' => $issueData['estimate'] !== null ? (int) ($issueData['estimate'] * 3600) : $task->estimated_time,
            ]);
        } else {
            $task = new Task();
            $task->linear_id = $issueData['id'];
            $task->name = $issueData['title'];
            $task->organization_id = $organization->getKey();
            $task->project_id = $projectId;
            $task->done_at = $doneAt;
            $task->estimated_time = $issueData['estimate'] !== null ? (int) ($issueData['estimate'] * 3600) : null;
            $task->save();
        }

        return $task;
    }

    /**
     * @param  array{id: string, name: string}  $projectData
     */
    private function upsertProject(Organization $organization, array $projectData): Project
    {
        $project = Project::where('linear_project_id', $projectData['id'])
            ->where('organization_id', $organization->getKey())
            ->first();

        if ($project !== null) {
            return $project;
        }

        $project = new Project();
        $project->linear_project_id = $projectData['id'];
        $project->name = $projectData['name'];
        $project->organization_id = $organization->getKey();
        $project->is_billable = false;
        $project->save();

        return $project;
    }
}
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test extensions/Linear/tests/Unit/Services/LinearSyncServiceTest.php`
Expected: PASS (5 tests).

**Step 5: Commit**

```bash
git add extensions/Linear/app/Services/LinearSyncService.php extensions/Linear/tests/
git commit -m "feat(linear): add sync service with task/project upsert logic"
```

---

### Task 6: OAuth Controller and Routes

**Files:**
- Create: `extensions/Linear/app/Http/Controllers/LinearOAuthController.php`
- Modify: `extensions/Linear/routes/api.php`
- Create: `extensions/Linear/tests/Unit/Controllers/LinearOAuthControllerTest.php`

**Step 1: Write the failing test**

Test the OAuth callback endpoint — the part that exchanges the code for tokens and stores the integration.

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Unit\Controllers;

use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Extensions\Linear\Models\LinearIntegration;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use Tests\TestCaseWithDatabase;

class LinearOAuthControllerTest extends TestCaseWithDatabase
{
    public function test_connect_redirects_to_linear_oauth(): void
    {
        $data = $this->createUserWithPermission(['linear:manage']);
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.linear.connect', [
            'organization' => $data->organization->getKey(),
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['redirect_url']);
        $this->assertStringContains('linear.app/oauth/authorize', $response->json('redirect_url'));
    }

    public function test_callback_exchanges_code_and_stores_integration(): void
    {
        $data = $this->createUserWithPermission(['linear:manage']);
        Passport::actingAs($data->user);

        // Mock Linear token exchange
        Http::fake([
            'api.linear.app/oauth/token' => Http::response([
                'access_token' => 'lin_access_123',
                'token_type' => 'Bearer',
                'expires_in' => 86400,
                'scope' => ['read'],
            ], 200),
            'api.linear.app/graphql' => Http::response([
                'data' => ['viewer' => ['id' => 'linear-user-abc', 'name' => 'Test User']],
            ], 200),
        ]);

        $response = $this->getJson(route('api.v1.linear.callback', [
            'organization' => $data->organization->getKey(),
            'code' => 'auth-code-123',
        ]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('linear_integrations', [
            'user_id' => $data->user->getKey(),
            'organization_id' => $data->organization->getKey(),
            'linear_user_id' => 'linear-user-abc',
        ]);
    }

    public function test_status_returns_connected_when_integration_exists(): void
    {
        $data = $this->createUserWithPermission(['linear:manage']);
        Passport::actingAs($data->user);

        LinearIntegration::create([
            'user_id' => $data->user->getKey(),
            'organization_id' => $data->organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'user-123',
        ]);

        $response = $this->getJson(route('api.v1.linear.status', [
            'organization' => $data->organization->getKey(),
        ]));

        $response->assertStatus(200);
        $response->assertJson(['connected' => true]);
    }

    public function test_disconnect_removes_integration(): void
    {
        $data = $this->createUserWithPermission(['linear:manage']);
        Passport::actingAs($data->user);

        LinearIntegration::create([
            'user_id' => $data->user->getKey(),
            'organization_id' => $data->organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'user-123',
        ]);

        $response = $this->deleteJson(route('api.v1.linear.disconnect', [
            'organization' => $data->organization->getKey(),
        ]));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('linear_integrations', [
            'user_id' => $data->user->getKey(),
        ]);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test extensions/Linear/tests/Unit/Controllers/LinearOAuthControllerTest.php`
Expected: FAIL — route not defined / controller not found.

**Step 3: Write the controller**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Http\Controllers;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Organization;
use Extensions\Linear\Models\LinearIntegration;
use Extensions\Linear\Services\LinearGraphQLClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LinearOAuthController extends Controller
{
    private const AUTH_URL = 'https://linear.app/oauth/authorize';
    private const TOKEN_URL = 'https://api.linear.app/oauth/token';

    public function connect(Organization $organization, Request $request): JsonResponse
    {
        $this->checkPermission($organization, 'linear:manage');

        $state = Str::random(40);
        session(['linear_oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => config('linear.client_id'),
            'redirect_uri' => route('api.v1.linear.callback', ['organization' => $organization->getKey()]),
            'response_type' => 'code',
            'scope' => 'read',
            'state' => $state,
            'actor' => 'user',
        ]);

        return new JsonResponse([
            'redirect_url' => self::AUTH_URL . '?' . $params,
        ]);
    }

    public function callback(Organization $organization, Request $request): JsonResponse
    {
        $this->checkPermission($organization, 'linear:manage');

        $tokenResponse = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'authorization_code',
            'code' => $request->input('code'),
            'client_id' => config('linear.client_id'),
            'client_secret' => config('linear.client_secret'),
            'redirect_uri' => route('api.v1.linear.callback', ['organization' => $organization->getKey()]),
        ]);

        $tokenData = $tokenResponse->json();
        $accessToken = $tokenData['access_token'];
        $expiresIn = $tokenData['expires_in'] ?? 86400;

        $client = new LinearGraphQLClient($accessToken);
        $viewer = $client->query('{ viewer { id name } }');

        LinearIntegration::updateOrCreate(
            [
                'user_id' => $this->user()->getKey(),
                'organization_id' => $organization->getKey(),
            ],
            [
                'access_token' => $accessToken,
                'refresh_token' => $tokenData['refresh_token'] ?? '',
                'token_expires_at' => now()->addSeconds($expiresIn),
                'linear_user_id' => $viewer['viewer']['id'],
            ]
        );

        return new JsonResponse(['message' => 'Connected to Linear', 'linear_user' => $viewer['viewer']['name']]);
    }

    public function status(Organization $organization): JsonResponse
    {
        $this->checkPermission($organization, 'linear:manage');

        $integration = LinearIntegration::where('user_id', $this->user()->getKey())
            ->where('organization_id', $organization->getKey())
            ->first();

        if ($integration === null) {
            return new JsonResponse(['connected' => false]);
        }

        return new JsonResponse([
            'connected' => true,
            'linear_user_id' => $integration->linear_user_id,
            'last_synced_at' => $integration->last_synced_at?->toIso8601String(),
        ]);
    }

    public function disconnect(Organization $organization): JsonResponse
    {
        $this->checkPermission($organization, 'linear:manage');

        $integration = LinearIntegration::where('user_id', $this->user()->getKey())
            ->where('organization_id', $organization->getKey())
            ->first();

        if ($integration !== null) {
            // Revoke token at Linear
            Http::asForm()->post('https://api.linear.app/oauth/revoke', [
                'client_id' => config('linear.client_id'),
                'client_secret' => config('linear.client_secret'),
                'token' => $integration->access_token,
            ]);

            $integration->delete();
        }

        return new JsonResponse(null, 204);
    }
}
```

**Step 4: Register routes in `extensions/Linear/routes/api.php`**

```php
<?php

declare(strict_types=1);

use Extensions\Linear\Http\Controllers\LinearOAuthController;
use Extensions\Linear\Http\Controllers\LinearWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->name('api.v1.linear.')->group(static function (): void {
    // Authenticated routes
    Route::middleware(['auth:api', 'verified'])->group(static function (): void {
        Route::prefix('organizations/{organization}')->group(static function (): void {
            Route::get('/linear/connect', [LinearOAuthController::class, 'connect'])->name('connect');
            Route::get('/linear/callback', [LinearOAuthController::class, 'callback'])->name('callback');
            Route::get('/linear/status', [LinearOAuthController::class, 'status'])->name('status');
            Route::delete('/linear/disconnect', [LinearOAuthController::class, 'disconnect'])->name('disconnect');
        });
    });

    // Public webhook endpoint (signature-verified, no auth)
    Route::post('/linear/webhook', [LinearWebhookController::class, 'handle'])->name('webhook');
});
```

**Step 5: Create extension config file**

Create: `extensions/Linear/config/linear.php`

```php
<?php

declare(strict_types=1);

return [
    'client_id' => env('LINEAR_CLIENT_ID'),
    'client_secret' => env('LINEAR_CLIENT_SECRET'),
];
```

Update service provider to merge config:

```php
public function register(): void
{
    $this->mergeConfigFrom(__DIR__ . '/../../config/linear.php', 'linear');
}
```

**Step 6: Run tests to verify they pass**

Run: `php artisan test extensions/Linear/tests/Unit/Controllers/LinearOAuthControllerTest.php`
Expected: PASS (4 tests). Note: Some tests may need adjustments depending on how `checkPermission` handles the custom `linear:manage` permission — may need to use `createUserWithPermission` with appropriate permissions or mock it.

**Step 7: Commit**

```bash
git add extensions/Linear/app/Http/ extensions/Linear/routes/ extensions/Linear/config/ extensions/Linear/tests/
git commit -m "feat(linear): add OAuth controller with connect/callback/status/disconnect"
```

---

### Task 7: Webhook Controller

**Files:**
- Create: `extensions/Linear/app/Http/Controllers/LinearWebhookController.php`
- Create: `extensions/Linear/app/Jobs/ProcessLinearWebhook.php`
- Create: `extensions/Linear/tests/Unit/Controllers/LinearWebhookControllerTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Unit\Controllers;

use App\Models\Organization;
use App\Models\User;
use Extensions\Linear\Models\LinearIntegration;
use Illuminate\Support\Facades\Queue;
use Extensions\Linear\Jobs\ProcessLinearWebhook;
use Tests\TestCaseWithDatabase;

class LinearWebhookControllerTest extends TestCaseWithDatabase
{
    public function test_valid_webhook_dispatches_job_and_returns_200(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $secret = 'test-webhook-secret';

        LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'linear-user-123',
            'webhook_secret' => $secret,
        ]);

        $payload = json_encode([
            'action' => 'create',
            'type' => 'Issue',
            'data' => [
                'id' => 'issue-1',
                'title' => 'Test issue',
                'assignee' => ['id' => 'linear-user-123'],
                'state' => ['type' => 'started'],
                'project' => null,
                'estimate' => null,
            ],
            'webhookTimestamp' => now()->getTimestampMs(),
        ]);

        $signature = hash_hmac('sha256', $payload, $secret);

        $response = $this->postJson(
            route('api.v1.linear.webhook'),
            json_decode($payload, true),
            [
                'Linear-Signature' => $signature,
                'Content-Type' => 'application/json',
            ]
        );

        $response->assertStatus(200);
        Queue::assertPushed(ProcessLinearWebhook::class);
    }

    public function test_invalid_signature_returns_401(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'linear-user-123',
            'webhook_secret' => 'real-secret',
        ]);

        $payload = json_encode(['action' => 'create', 'type' => 'Issue', 'data' => [], 'webhookTimestamp' => now()->getTimestampMs()]);

        $response = $this->postJson(
            route('api.v1.linear.webhook'),
            json_decode($payload, true),
            ['Linear-Signature' => 'bad-signature']
        );

        $response->assertStatus(401);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test extensions/Linear/tests/Unit/Controllers/LinearWebhookControllerTest.php`
Expected: FAIL — controller/job not found.

**Step 3: Write the webhook controller**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Http\Controllers;

use Extensions\Linear\Jobs\ProcessLinearWebhook;
use Extensions\Linear\Model\LinearIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LinearWebhookController
{
    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('Linear-Signature');
        $payload = $request->getContent();

        if ($signature === null) {
            return new JsonResponse(['error' => 'Missing signature'], 401);
        }

        // Find integration by checking signature against all webhook secrets
        $verified = false;
        $integrations = LinearIntegration::whereNotNull('webhook_secret')->get();

        foreach ($integrations as $integration) {
            $expected = hash_hmac('sha256', $payload, $integration->webhook_secret);
            if (hash_equals($expected, $signature)) {
                $verified = true;
                break;
            }
        }

        if (! $verified) {
            Log::warning('Linear webhook signature verification failed');
            return new JsonResponse(['error' => 'Invalid signature'], 401);
        }

        $data = $request->all();

        // Only process Issue events
        if (($data['type'] ?? '') !== 'Issue') {
            return new JsonResponse(['message' => 'Ignored']);
        }

        ProcessLinearWebhook::dispatch($data);

        return new JsonResponse(['message' => 'Accepted']);
    }
}
```

**Step 4: Write the webhook processing job**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Jobs;

use Extensions\Linear\Models\LinearIntegration;
use Extensions\Linear\Services\LinearSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessLinearWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly array $payload,
    ) {}

    public function handle(LinearSyncService $syncService): void
    {
        $action = $this->payload['action'] ?? '';
        $issueData = $this->payload['data'] ?? [];
        $assigneeId = $issueData['assignee']['id'] ?? null;

        if ($assigneeId === null) {
            return;
        }

        // Find integrations matching this Linear user
        $integrations = LinearIntegration::where('linear_user_id', $assigneeId)->get();

        foreach ($integrations as $integration) {
            if ($action === 'create' || $action === 'update') {
                $syncService->upsertTask($integration->organization, $issueData);
            }
            // 'remove' action: intentionally do nothing — preserve tracked time
        }
    }
}
```

**Step 5: Run tests to verify they pass**

Run: `php artisan test extensions/Linear/tests/Unit/Controllers/LinearWebhookControllerTest.php`
Expected: PASS (2 tests).

**Step 6: Commit**

```bash
git add extensions/Linear/app/Http/Controllers/LinearWebhookController.php extensions/Linear/app/Jobs/ extensions/Linear/tests/
git commit -m "feat(linear): add webhook controller with signature verification and job dispatch"
```

---

### Task 8: Polling Sync Command and Job

**Files:**
- Create: `extensions/Linear/app/Console/SyncLinearCommand.php`
- Create: `extensions/Linear/app/Jobs/SyncLinearIssuesForUser.php`
- Create: `extensions/Linear/tests/Unit/Jobs/SyncLinearIssuesForUserTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Unit\Jobs;

use App\Models\Organization;
use App\Models\User;
use Extensions\Linear\Jobs\SyncLinearIssuesForUser;
use Extensions\Linear\Models\LinearIntegration;
use Illuminate\Support\Facades\Http;
use Tests\TestCaseWithDatabase;

class SyncLinearIssuesForUserTest extends TestCaseWithDatabase
{
    public function test_sync_fetches_assigned_issues_and_creates_tasks(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $integration = LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'valid-token',
            'refresh_token' => 'valid-refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'linear-user-abc',
            'last_synced_at' => now()->subHour(),
        ]);

        Http::fake([
            'api.linear.app/graphql' => Http::response([
                'data' => [
                    'issues' => [
                        'nodes' => [
                            [
                                'id' => 'issue-1',
                                'title' => 'First issue',
                                'state' => ['type' => 'started'],
                                'project' => null,
                                'estimate' => null,
                                'updatedAt' => now()->toIso8601String(),
                            ],
                            [
                                'id' => 'issue-2',
                                'title' => 'Second issue',
                                'state' => ['type' => 'completed'],
                                'project' => ['id' => 'proj-1', 'name' => 'Project Alpha'],
                                'estimate' => 3.0,
                                'updatedAt' => now()->toIso8601String(),
                            ],
                        ],
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                    ],
                ],
            ], 200),
        ]);

        $job = new SyncLinearIssuesForUser($integration);
        $job->handle();

        $this->assertDatabaseHas('tasks', [
            'linear_id' => 'issue-1',
            'name' => 'First issue',
            'organization_id' => $organization->getKey(),
        ]);
        $this->assertDatabaseHas('tasks', [
            'linear_id' => 'issue-2',
            'name' => 'Second issue',
        ]);

        $integration->refresh();
        $this->assertNotNull($integration->last_synced_at);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test extensions/Linear/tests/Unit/Jobs/SyncLinearIssuesForUserTest.php`
Expected: FAIL — class not found.

**Step 3: Write the sync job**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Jobs;

use Extensions\Linear\Models\LinearIntegration;
use Extensions\Linear\Services\LinearGraphQLClient;
use Extensions\Linear\Services\LinearSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncLinearIssuesForUser implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly LinearIntegration $integration,
    ) {}

    public function handle(LinearSyncService $syncService): void
    {
        $client = new LinearGraphQLClient($this->integration->access_token);

        $variables = [
            'assigneeId' => $this->integration->linear_user_id,
        ];

        $filter = '{ assignee: { id: { eq: $assigneeId } }';
        if ($this->integration->last_synced_at !== null) {
            $filter .= ', updatedAt: { gt: "' . $this->integration->last_synced_at->toIso8601String() . '" }';
        }
        $filter .= ' }';

        $query = <<<GRAPHQL
            query (\$assigneeId: String!, \$after: String) {
                issues(
                    filter: $filter,
                    orderBy: updatedAt,
                    first: 50,
                    after: \$after
                ) {
                    nodes {
                        id
                        title
                        state { type }
                        project { id name }
                        estimate
                        updatedAt
                    }
                    pageInfo {
                        hasNextPage
                        endCursor
                    }
                }
            }
        GRAPHQL;

        $hasNextPage = true;
        $cursor = null;

        while ($hasNextPage) {
            if ($cursor !== null) {
                $variables['after'] = $cursor;
            }

            try {
                $result = $client->query($query, $variables);
            } catch (\RuntimeException $e) {
                Log::error('Linear sync failed: ' . $e->getMessage(), [
                    'integration_id' => $this->integration->getKey(),
                ]);
                return;
            }

            $issues = $result['issues']['nodes'] ?? [];
            foreach ($issues as $issue) {
                $syncService->upsertTask($this->integration->organization, $issue);
            }

            $pageInfo = $result['issues']['pageInfo'];
            $hasNextPage = $pageInfo['hasNextPage'];
            $cursor = $pageInfo['endCursor'];
        }

        $this->integration->update(['last_synced_at' => now()]);
    }
}
```

**Step 4: Write the artisan command**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Console;

use Extensions\Linear\Jobs\SyncLinearIssuesForUser;
use Extensions\Linear\Models\LinearIntegration;
use Illuminate\Console\Command;

class SyncLinearCommand extends Command
{
    protected $signature = 'linear:sync';
    protected $description = 'Sync Linear issues for all connected users';

    public function handle(): int
    {
        $integrations = LinearIntegration::all();

        foreach ($integrations as $integration) {
            SyncLinearIssuesForUser::dispatch($integration);
        }

        $this->info("Dispatched sync jobs for {$integrations->count()} integration(s).");

        return self::SUCCESS;
    }
}
```

Register the command and schedule in the service provider:

```php
public function boot(): void
{
    $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

    if ($this->app->runningInConsole()) {
        $this->commands([
            \Extensions\Linear\Console\SyncLinearCommand::class,
        ]);
    }
}
```

**Step 5: Run tests to verify they pass**

Run: `php artisan test extensions/Linear/tests/Unit/Jobs/SyncLinearIssuesForUserTest.php`
Expected: PASS (1 test).

**Step 6: Commit**

```bash
git add extensions/Linear/app/Console/ extensions/Linear/app/Jobs/SyncLinearIssuesForUser.php extensions/Linear/tests/
git commit -m "feat(linear): add polling sync job and artisan command"
```

---

### Task 9: Schedule the Polling Command

**Files:**
- Modify: `extensions/Linear/app/Providers/LinearServiceProvider.php`

**Step 1: Add schedule registration to the service provider**

Add to the `boot()` method:

```php
$this->callAfterResolving(\Illuminate\Console\Scheduling\Schedule::class, function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
    $schedule->command('linear:sync')->everyFifteenMinutes();
});
```

**Step 2: Verify the schedule is registered**

Run: `php artisan schedule:list | grep linear`
Expected: Shows `linear:sync` every 15 minutes.

**Step 3: Commit**

```bash
git add extensions/Linear/app/Providers/LinearServiceProvider.php
git commit -m "feat(linear): schedule polling sync every 15 minutes"
```

---

### Task 10: Integration Test — Full Sync Round Trip

**Files:**
- Create: `extensions/Linear/tests/Feature/LinearSyncIntegrationTest.php`

**Step 1: Write the integration test**

```php
<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Feature;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Extensions\Linear\Jobs\SyncLinearIssuesForUser;
use Extensions\Linear\Models\LinearIntegration;
use Illuminate\Support\Facades\Http;
use Tests\TestCaseWithDatabase;

class LinearSyncIntegrationTest extends TestCaseWithDatabase
{
    public function test_full_sync_round_trip_creates_and_updates_tasks(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $integration = LinearIntegration::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'lu-1',
            'last_synced_at' => null,
        ]);

        // First sync: create issues
        Http::fake([
            'api.linear.app/graphql' => Http::response([
                'data' => [
                    'issues' => [
                        'nodes' => [
                            ['id' => 'i-1', 'title' => 'Issue One', 'state' => ['type' => 'started'], 'project' => null, 'estimate' => null, 'updatedAt' => now()->toIso8601String()],
                        ],
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                    ],
                ],
            ]),
        ]);

        (new SyncLinearIssuesForUser($integration))->handle();

        $this->assertDatabaseHas('tasks', ['linear_id' => 'i-1', 'name' => 'Issue One']);

        // Second sync: update issue title
        Http::fake([
            'api.linear.app/graphql' => Http::response([
                'data' => [
                    'issues' => [
                        'nodes' => [
                            ['id' => 'i-1', 'title' => 'Issue One Updated', 'state' => ['type' => 'completed'], 'project' => null, 'estimate' => null, 'updatedAt' => now()->toIso8601String()],
                        ],
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                    ],
                ],
            ]),
        ]);

        $integration->refresh();
        (new SyncLinearIssuesForUser($integration))->handle();

        $task = Task::where('linear_id', 'i-1')->first();
        $this->assertEquals('Issue One Updated', $task->name);
        $this->assertNotNull($task->done_at);
    }
}
```

**Step 2: Run the integration test**

Run: `php artisan test extensions/Linear/tests/Feature/LinearSyncIntegrationTest.php`
Expected: PASS (1 test).

**Step 3: Run full test suite to verify nothing is broken**

Run: `composer test`
Expected: All tests pass.

**Step 4: Commit**

```bash
git add extensions/Linear/tests/Feature/
git commit -m "feat(linear): add integration test for full sync round trip"
```
