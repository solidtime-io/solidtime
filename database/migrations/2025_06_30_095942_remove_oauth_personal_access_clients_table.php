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
        Schema::drop('oauth_personal_access_clients');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('oauth_personal_access_clients', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('client_id');
            $table->foreign('client_id')
                ->references('id')
                ->on('oauth_clients')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->timestamps();
        });
    }
};
