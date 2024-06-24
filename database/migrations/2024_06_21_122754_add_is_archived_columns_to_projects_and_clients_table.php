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
            $table->dateTime('archived_at')->nullable();
        });
        Schema::table('clients', function (Blueprint $table): void {
            $table->dateTime('archived_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('archived_at');
        });
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn('archived_at');
        });
    }
};
