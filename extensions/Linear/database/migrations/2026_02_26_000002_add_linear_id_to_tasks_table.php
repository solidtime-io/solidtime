<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->string('linear_id')->nullable()->index();
            $table->unique(['organization_id', 'linear_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropUnique(['organization_id', 'linear_id']);
            $table->dropColumn('linear_id');
        });
    }
};
