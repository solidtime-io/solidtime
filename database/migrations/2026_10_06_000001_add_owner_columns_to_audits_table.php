<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: Renaming columns and adding nullable columns without default are metadata-only operations in PostgreSQL,
     * so this migration is fast even for a large audits table.
     * The indexes and foreign keys for the new columns are added in a separate non-transactional migration.
     */
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table): void {
            $table->renameColumn('user_type', 'actor_type');
            $table->renameColumn('user_id', 'actor_id');
            $table->renameIndex('audits_user_id_user_type_index', 'audits_actor_id_actor_type_index');
            $table->uuid('owner_user_id')->nullable();
            $table->uuid('owner_organization_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table): void {
            $table->dropColumn('owner_user_id');
            $table->dropColumn('owner_organization_id');
            $table->renameIndex('audits_actor_id_actor_type_index', 'audits_user_id_user_type_index');
            $table->renameColumn('actor_type', 'user_type');
            $table->renameColumn('actor_id', 'user_id');
        });
    }
};
