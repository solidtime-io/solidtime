<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('milestone_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('phase_template_id');
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->foreign('phase_template_id')->references('id')->on('phase_templates')->cascadeOnDelete();
            $table->index('phase_template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_templates');
    }
};
