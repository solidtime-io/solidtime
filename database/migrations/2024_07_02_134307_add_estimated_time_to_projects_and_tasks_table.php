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
            $table->integer('estimated_time')->unsigned()->nullable();
        });
        Schema::table('tasks', function (Blueprint $table): void {
            $table->integer('estimated_time')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('estimated_time');
        });
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('estimated_time');
        });
    }
};
