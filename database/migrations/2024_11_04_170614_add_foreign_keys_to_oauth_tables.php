<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('oauth_access_tokens')
            ->whereNotNull('user_id')
            ->whereNotExists(function (Builder $query): void {
                $query->select('id')
                    ->from('users')
                    ->whereColumn('oauth_access_tokens.user_id', 'users.id');
            })
            ->delete();
        DB::table('oauth_access_tokens')
            ->whereNotExists(function (Builder $query): void {
                $query->select('id')
                    ->from('oauth_clients')
                    ->whereColumn('oauth_access_tokens.client_id', 'oauth_clients.id');
            })
            ->delete();
        Schema::table('oauth_access_tokens', function (Blueprint $table): void {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreign('client_id')
                ->references('id')
                ->on('oauth_clients')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
        DB::table('oauth_auth_codes')
            ->whereNotExists(function (Builder $query): void {
                $query->select('id')
                    ->from('users')
                    ->whereColumn('oauth_auth_codes.user_id', 'users.id');
            })
            ->delete();
        DB::table('oauth_auth_codes')
            ->whereNotExists(function (Builder $query): void {
                $query->select('id')
                    ->from('oauth_clients')
                    ->whereColumn('oauth_auth_codes.client_id', 'oauth_clients.id');
            })
            ->delete();
        Schema::table('oauth_auth_codes', function (Blueprint $table): void {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreign('client_id')
                ->references('id')
                ->on('oauth_clients')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
        DB::table('oauth_clients')
            ->whereNotNull('user_id')
            ->whereNotExists(function (Builder $query): void {
                $query->select('id')
                    ->from('users')
                    ->whereColumn('oauth_clients.user_id', 'users.id');
            })
            ->delete();
        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
        Schema::table('oauth_personal_access_clients', function (Blueprint $table): void {
            $table->foreign('client_id')
                ->references('id')
                ->on('oauth_clients')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['client_id']);
        });
        Schema::table('oauth_auth_codes', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['client_id']);
        });
        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });
        Schema::table('oauth_personal_access_clients', function (Blueprint $table): void {
            $table->dropForeign(['client_id']);
        });
    }
};
