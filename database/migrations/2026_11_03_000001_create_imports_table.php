<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            // Importer key, see ImporterProvider
            $table->string('type');
            $table->string('source');
            $table->string('status');
            // Timezone of the user that started the import, used to interpret dates without timezone
            $table->string('timezone');
            // Path of the uploaded file on the private disk, null for non-file imports or after the file was pruned
            $table->string('file_path')->nullable();
            // Encrypted credentials for imports via a third party API, removed after the import finished
            $table->text('credentials')->nullable();
            $table->jsonb('options')->nullable();
            // Imports started by a super admin (admin panel) are not limited by the configured import limits
            $table->boolean('ignore_limits')->default(false);
            $table->jsonb('report')->nullable();
            // Error message shown to the user
            $table->text('error_message')->nullable();
            // Technical error details (exception chain and stack trace), only visible in the admin panel
            $table->text('error_details')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->uuid('organization_id');
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            // User that started the import, this is not necessarily a member of the organization (admin panel)
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();
            $table->index(['organization_id', 'created_at']);
            $table->index('finished_at');
        });

        // Only one import per organization can be pending or running at the same time
        DB::statement("CREATE UNIQUE INDEX imports_organization_id_active_unique ON imports (organization_id) WHERE status IN ('pending', 'running')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
