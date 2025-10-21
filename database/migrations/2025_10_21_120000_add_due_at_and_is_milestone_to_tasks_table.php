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
        Schema::table('tasks', function (Blueprint $table): void {
            // Nullable deadline for tasks/milestones
            $table->dateTime('due_at')->nullable();
            // Flag to mark a task as a milestone
            $table->boolean('is_milestone')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('due_at');
            $table->dropColumn('is_milestone');
        });
    }
};
