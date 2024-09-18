<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->integer('spent_time')->unsigned()->default(0);
        });
        Schema::table('tasks', function (Blueprint $table): void {
            $table->integer('spent_time')->unsigned()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('spent_time');
        });
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('spent_time');
        });
    }
};
