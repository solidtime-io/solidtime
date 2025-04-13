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
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('number_format')->default(config('app.localization.default_number_format'))->nullable(false);
            $table->string('currency_format')->default(config('app.localization.default_currency_format'))->nullable(false);
            $table->string('date_format')->default(config('app.localization.default_date_format'))->nullable(false);
            $table->string('interval_format')->default(config('app.localization.default_interval_format'))->nullable(false);
            $table->string('time_format')->default(config('app.localization.default_time_format'))->nullable(false);
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('number_format')->default(null)->nullable(false)->change();
            $table->string('currency_format')->default(null)->nullable(false)->change();
            $table->string('date_format')->default(null)->nullable(false)->change();
            $table->string('interval_format')->default(null)->nullable(false)->change();
            $table->string('time_format')->default(null)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn('number_format');
            $table->dropColumn('currency_format');
            $table->dropColumn('date_format');
            $table->dropColumn('interval_format');
            $table->dropColumn('time_format');
        });
    }
};
