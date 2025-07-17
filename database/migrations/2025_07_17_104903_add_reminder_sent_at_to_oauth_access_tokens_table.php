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
        Schema::table('oauth_access_tokens', function (Blueprint $table): void {
            $table->dateTime('reminder_sent_at')->nullable();
            $table->dateTime('expired_info_sent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table): void {
            $table->dropColumn('reminder_sent_at');
            $table->dropColumn('expired_info_sent_at');
        });
    }
};
