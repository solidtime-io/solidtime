<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_milestone_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('project_phase_template_id');
            $table->foreign('project_phase_template_id')
                ->references('id')
                ->on('project_phase_templates')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('name', 300);
            $table->boolean('is_milestone')->default(true);
            $table->integer('due_offset_days')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestone_templates');
    }
};
