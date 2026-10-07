<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PostgreSQL cannot build an index concurrently inside a transaction.
     * Keeping this migration non-transactional prevents long write locks on the (large) audits table in production.
     * Every step is idempotent, so the migration can be re-run if it fails halfway.
     *
     * @var bool
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createIndex('audits_owner_user_id_index', 'owner_user_id');
        $this->createIndex('audits_owner_organization_id_index', 'owner_organization_id');

        $this->createForeignKey('audits_owner_user_id_foreign', 'owner_user_id', 'users');
        $this->createForeignKey('audits_owner_organization_id_foreign', 'owner_organization_id', 'organizations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE audits DROP CONSTRAINT IF EXISTS audits_owner_user_id_foreign');
        DB::statement('ALTER TABLE audits DROP CONSTRAINT IF EXISTS audits_owner_organization_id_foreign');
        DB::statement('DROP INDEX'.$this->concurrently().' IF EXISTS audits_owner_user_id_index');
        DB::statement('DROP INDEX'.$this->concurrently().' IF EXISTS audits_owner_organization_id_index');
    }

    private function createIndex(string $index, string $column): void
    {
        $state = DB::selectOne(
            <<<'SQL'
                SELECT pg_index.indisvalid::int AS valid
                FROM pg_index
                JOIN pg_class ON pg_class.oid = pg_index.indexrelid
                JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
                WHERE pg_namespace.nspname = current_schema()
                  AND pg_class.relname = ?
                SQL,
            [$index],
        );

        if ($state !== null && (bool) $state->valid) {
            return;
        }

        // A failed concurrent index build leaves an invalid index behind, that needs to be dropped before rebuilding
        if ($state !== null) {
            DB::statement('DROP INDEX'.$this->concurrently().' '.$index);
        }

        DB::statement('CREATE INDEX'.$this->concurrently().' '.$index.' ON audits ('.$column.')');
    }

    private function createForeignKey(string $constraint, string $column, string $referencedTable): void
    {
        $exists = DB::selectOne(
            <<<'SQL'
                SELECT 1
                FROM pg_constraint
                JOIN pg_namespace ON pg_namespace.oid = pg_constraint.connamespace
                WHERE pg_namespace.nspname = current_schema()
                  AND pg_constraint.conname = ?
                SQL,
            [$constraint],
        ) !== null;

        // Note: Adding the constraint as NOT VALID only needs a short lock, the validation of the existing rows
        // afterward does not block reads or writes on the audits table.
        if (! $exists) {
            DB::statement('ALTER TABLE audits ADD CONSTRAINT '.$constraint.' FOREIGN KEY ('.$column.') REFERENCES '.$referencedTable.' (id) ON DELETE CASCADE NOT VALID');
        }

        DB::statement('ALTER TABLE audits VALIDATE CONSTRAINT '.$constraint);
    }

    private function concurrently(): string
    {
        return DB::transactionLevel() === 0 ? ' CONCURRENTLY' : '';
    }
};
