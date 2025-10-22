<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phase_milestones', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('phase_id');
            $table->string('name');
            $table->unsignedInteger('seq')->default(0);
            $table->date('planned_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->string('status')->default('Planned'); // Planned, InProgress, Complete, Blocked
            $table->uuid('responsible_user_id')->nullable();
            $table->string('responsible_alias')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('phase_id')->references('id')->on('project_phases')->cascadeOnDelete();
            $table->index('phase_id');
            $table->index(['phase_id', 'seq']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phase_milestones');
    }
};
