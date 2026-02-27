<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('linear_integrations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('organization_id');
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('token_expires_at');
            $table->string('linear_user_id');
            $table->text('webhook_secret')->nullable();
            $table->string('webhook_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['user_id', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('linear_integrations');
    }
};
