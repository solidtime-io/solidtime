<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\info as consoleInfo;

return new class extends Migration
{
    /**
     * PostgreSQL cannot build or drop an index concurrently inside a transaction.
     * Keeping this migration non-transactional prevents long write locks in production.
     *
     * @var bool
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tenant-scoped client pagination ordered by newest first; also covers the organization FK.
        $this->runIndexOperation('create', 'clients_organization_created_id_index', 'CREATE INDEX CONCURRENTLY clients_organization_created_id_index ON clients (organization_id, created_at DESC, id)');

        // Tenant-scoped project pagination ordered by newest first; also covers the organization FK.
        $this->runIndexOperation('create', 'projects_organization_created_id_index', 'CREATE INDEX CONCURRENTLY projects_organization_created_id_index ON projects (organization_id, created_at DESC, id)');
        // Speeds client relationship loads and the FK check when a client is deleted or its ID changes.
        $this->runIndexOperation('create', 'projects_client_id_index', 'CREATE INDEX CONCURRENTLY projects_client_id_index ON projects (client_id)');

        // Tenant-scoped task pagination ordered by newest first; also covers the organization FK.
        $this->runIndexOperation('create', 'tasks_organization_created_id_index', 'CREATE INDEX CONCURRENTLY tasks_organization_created_id_index ON tasks (organization_id, created_at DESC, id)');
        // Speeds project task lists and the FK check when a project is deleted or its ID changes.
        $this->runIndexOperation('create', 'tasks_project_id_index', 'CREATE INDEX CONCURRENTLY tasks_project_id_index ON tasks (project_id)');

        // Tenant-scoped tag pagination ordered by newest first; also covers the organization FK.
        $this->runIndexOperation('create', 'tags_organization_created_id_index', 'CREATE INDEX CONCURRENTLY tags_organization_created_id_index ON tags (organization_id, created_at DESC, id)');

        // Tenant-scoped report pagination ordered by newest first; also covers the organization FK.
        $this->runIndexOperation('create', 'reports_organization_created_id_index', 'CREATE INDEX CONCURRENTLY reports_organization_created_id_index ON reports (organization_id, created_at DESC, id)');

        // Tenant-scoped member pagination; the existing (organization_id, user_id) unique index remains for membership lookup.
        $this->runIndexOperation('create', 'members_organization_created_id_index', 'CREATE INDEX CONCURRENTLY members_organization_created_id_index ON members (organization_id, created_at DESC, id)');
        // Supports reverse user-to-membership lookups and the FK check when a user is deleted or its ID changes.
        $this->runIndexOperation('create', 'members_user_id_index', 'CREATE INDEX CONCURRENTLY members_user_id_index ON members (user_id)');

        // The existing (project_id, user_id) unique index covers project_id, but member_id and legacy user_id need reverse indexes.
        $this->runIndexOperation('create', 'project_members_member_id_index', 'CREATE INDEX CONCURRENTLY project_members_member_id_index ON project_members (member_id)');
        $this->runIndexOperation('create', 'project_members_user_id_index', 'CREATE INDEX CONCURRENTLY project_members_user_id_index ON project_members (user_id)');

        // Supports organization deletion/current-team cleanup and the FK check on users.current_team_id.
        $this->runIndexOperation('create', 'users_current_team_id_index', 'CREATE INDEX CONCURRENTLY users_current_team_id_index ON users (current_team_id)');

        // Filament loads the newest audits first; this avoids scanning and sorting the large append-only audit table.
        $this->runIndexOperation('create', 'audits_created_at_index', 'CREATE INDEX CONCURRENTLY audits_created_at_index ON audits (created_at DESC)');

        // Main tenant time-entry range/pagination path, including its start DESC, id ordering; also covers the organization FK.
        $this->runIndexOperation('create', 'time_entries_organization_start_id_index', 'CREATE INDEX CONCURRENTLY time_entries_organization_start_id_index ON time_entries (organization_id, start DESC, id)');
        // Filament lists time entries globally by creation time, so the tenant-prefixed index cannot provide this ordering.
        $this->runIndexOperation('create', 'time_entries_created_at_index', 'CREATE INDEX CONCURRENTLY time_entries_created_at_index ON time_entries (created_at DESC)');
        // Dashboard history is consistently filtered by user and organization, then bounded by start; user first also covers its FK.
        $this->runIndexOperation('create', 'time_entries_user_organization_start_index', 'CREATE INDEX CONCURRENTLY time_entries_user_organization_start_index ON time_entries (user_id, organization_id, start)');
        // Member timelines, overlap checks, and billable-rate updates start with member_id; also covers its FK.
        $this->runIndexOperation('create', 'time_entries_member_start_index', 'CREATE INDEX CONCURRENTLY time_entries_member_start_index ON time_entries (member_id, start)');
        // These relationship/filter indexes also prevent full scans for FK checks when parent rows change or are deleted.
        $this->runIndexOperation('create', 'time_entries_project_id_index', 'CREATE INDEX CONCURRENTLY time_entries_project_id_index ON time_entries (project_id)');
        $this->runIndexOperation('create', 'time_entries_task_id_index', 'CREATE INDEX CONCURRENTLY time_entries_task_id_index ON time_entries (task_id)');
        $this->runIndexOperation('create', 'time_entries_client_id_index', 'CREATE INDEX CONCURRENTLY time_entries_client_id_index ON time_entries (client_id)');
        // Active-timer checks touch only open entries, so a partial index stays small while serving the hot member_id lookup.
        $this->runIndexOperation('create', 'time_entries_active_member_index', 'CREATE INDEX CONCURRENTLY time_entries_active_member_index ON time_entries (member_id) WHERE "end" IS NULL');
        // whereJsonContains(tags, tag_id) compiles to JSONB containment, which is supported by a GIN index.
        $this->runIndexOperation('create', 'time_entries_tags_gin_index', 'CREATE INDEX CONCURRENTLY time_entries_tags_gin_index ON time_entries USING GIN (tags)');

        // Passport already indexes user_id; these indexes cover the other FK used during OAuth client deletion/update.
        $this->runIndexOperation('create', 'oauth_access_tokens_client_id_index', 'CREATE INDEX CONCURRENTLY oauth_access_tokens_client_id_index ON oauth_access_tokens (client_id)');
        $this->runIndexOperation('create', 'oauth_auth_codes_client_id_index', 'CREATE INDEX CONCURRENTLY oauth_auth_codes_client_id_index ON oauth_auth_codes (client_id)');

        // owner_id is already the leading column of oauth_clients_owner_id_owner_type_index.
        $this->runIndexOperation('drop', 'oauth_clients_user_id_index', 'DROP INDEX CONCURRENTLY oauth_clients_user_id_index');
        // Public report lookup starts with the unique share_secret index; no query filters only by this boolean.
        $this->runIndexOperation('drop', 'reports_is_public_index', 'DROP INDEX CONCURRENTLY reports_is_public_index');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'clients_organization_created_id_index',
            'projects_organization_created_id_index',
            'projects_client_id_index',
            'tasks_organization_created_id_index',
            'tasks_project_id_index',
            'tags_organization_created_id_index',
            'reports_organization_created_id_index',
            'members_organization_created_id_index',
            'members_user_id_index',
            'project_members_member_id_index',
            'project_members_user_id_index',
            'users_current_team_id_index',
            'audits_created_at_index',
            'time_entries_organization_start_id_index',
            'time_entries_created_at_index',
            'time_entries_user_organization_start_index',
            'time_entries_member_start_index',
            'time_entries_project_id_index',
            'time_entries_task_id_index',
            'time_entries_client_id_index',
            'time_entries_active_member_index',
            'time_entries_tags_gin_index',
            'oauth_access_tokens_client_id_index',
            'oauth_auth_codes_client_id_index',
        ];

        $concurrently = DB::transactionLevel() === 0 ? ' CONCURRENTLY' : '';
        foreach ($indexes as $index) {
            $this->runIndexOperation('drop', $index, 'DROP INDEX'.$concurrently.' IF EXISTS '.$index);
        }

        $this->runIndexOperation('create', 'oauth_clients_user_id_index', 'CREATE INDEX'.$concurrently.' oauth_clients_user_id_index ON oauth_clients (owner_id)');
        $this->runIndexOperation('create', 'reports_is_public_index', 'CREATE INDEX'.$concurrently.' reports_is_public_index ON reports (is_public)');
    }

    private function runIndexOperation(string $operation, string $index, string $statement): void
    {
        $indexState = $this->indexState($index);

        if ($operation === 'create' && $indexState === ['valid' => true, 'ready' => true]) {
            $this->writeProgress(sprintf('Skipping index [%s] because it already exists and is valid', $index));

            return;
        }

        if ($operation === 'drop' && $indexState === null) {
            $this->writeProgress(sprintf('Skipping index [%s] because it does not exist', $index));

            return;
        }

        if ($operation === 'create' && $indexState !== null) {
            $this->writeProgress(sprintf(
                'Index [%s] exists but is incomplete (valid=%s, ready=%s); dropping it before rebuilding',
                $index,
                $indexState['valid'] ? 'true' : 'false',
                $indexState['ready'] ? 'true' : 'false',
            ));
            $this->executeIndexStatement(
                'drop incomplete',
                $index,
                (DB::transactionLevel() === 0 ? 'DROP INDEX CONCURRENTLY ' : 'DROP INDEX ').$this->quoteIdentifier($index),
            );
        }

        $this->executeIndexStatement($operation, $index, $statement);
    }

    private function executeIndexStatement(string $operation, string $index, string $statement): void
    {
        $startedAt = microtime(true);
        $this->writeProgress(sprintf('Starting to %s index [%s]', $operation, $index));

        try {
            DB::statement($statement);
        } catch (Throwable $exception) {
            $this->writeProgress(sprintf(
                'Failed to %s index [%s] after %.2f seconds: %s',
                $operation,
                $index,
                microtime(true) - $startedAt,
                $exception->getMessage(),
            ));

            throw $exception;
        }

        $this->writeProgress(sprintf(
            'Finished %s index [%s] in %.2f seconds',
            $operation === 'create' ? 'creating' : 'dropping',
            $index,
            microtime(true) - $startedAt,
        ));
    }

    /**
     * @return array{valid: bool, ready: bool}|null
     */
    private function indexState(string $index): ?array
    {
        $state = DB::selectOne(
            <<<'SQL'
                SELECT pg_index.indisvalid::int AS valid, pg_index.indisready::int AS ready
                FROM pg_index
                JOIN pg_class ON pg_class.oid = pg_index.indexrelid
                JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
                WHERE pg_namespace.nspname = current_schema()
                  AND pg_class.relname = ?
                SQL,
            [$index],
        );

        if ($state === null) {
            return null;
        }

        return [
            'valid' => (bool) $state->valid,
            'ready' => (bool) $state->ready,
        ];
    }

    private function quoteIdentifier(string $identifier): string
    {
        return DB::connection()->getQueryGrammar()->wrap($identifier);
    }

    private function writeProgress(string $message): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $message = sprintf('[%s] %s', date(DATE_ATOM), $message);
        Log::info($message);
        consoleInfo($message);
    }
};
