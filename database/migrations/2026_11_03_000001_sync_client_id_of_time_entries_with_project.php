<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The client of a time entry is always the client of its project. The importers used to take
     * the client from the import file, so time entries without a project could end up with a
     * client, and the admin panel did not update the client when the project of a time entry changed.
     */
    public function up(): void
    {
        DB::table('time_entries')
            ->whereNull('project_id')
            ->whereNotNull('client_id')
            ->update([
                'client_id' => null,
            ]);

        DB::statement('
            UPDATE time_entries
            SET client_id = projects.client_id
            FROM projects
            WHERE time_entries.project_id = projects.id
              AND time_entries.client_id IS DISTINCT FROM projects.client_id
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
