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
        Schema::table('time_entries', function (Blueprint $table) {
            $table->foreignUuid('client_id')
                ->nullable()
                ->constrained('clients')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
        DB::statement('
            update time_entries
            set client_id = clients.id
            from projects
            join clients on projects.client_id = clients.id
            where time_entries.project_id = projects.id
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
