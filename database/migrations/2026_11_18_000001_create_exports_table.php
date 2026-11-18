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
        Schema::create('exports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('status');
            // Path of the export file on the private disk, null until the export is completed or after the file was pruned
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->text('error_details')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->uuid('organization_id');
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            // User that started the export
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

        // Only one export per organization can be pending or running at the same time
        DB::statement("CREATE UNIQUE INDEX exports_organization_id_active_unique ON exports (organization_id) WHERE status IN ('pending', 'running')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
