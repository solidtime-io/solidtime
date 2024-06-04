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
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_billable')->default(false);
        });
        DB::statement('
            update projects
            set is_billable = true
            where projects.billable_rate is not null and projects.billable_rate > 0
        ');
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_billable')->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('is_billable');
        });
    }
};
