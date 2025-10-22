<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('planner_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->integer('offset_days');
            $table->json('applies_to')->nullable(); // e.g., ["worktops","curtains","carpets"] or tag IDs
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_rules');
    }
};
