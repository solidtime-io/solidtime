<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('organizations')
            ->where('billable_rate', '=', 0)
            ->update(['billable_rate' => null]);
        DB::table('project_members')
            ->where('billable_rate', '=', 0)
            ->update(['billable_rate' => null]);
        DB::table('projects')
            ->where('billable_rate', '=', 0)
            ->update(['billable_rate' => null]);
        DB::table('members')
            ->where('billable_rate', '=', 0)
            ->update(['billable_rate' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
