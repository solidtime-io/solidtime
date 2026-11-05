<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refresh tokens had no foreign key to their access token. Deleting a user used to delete the
     * access tokens but not the refresh tokens, so a still-valid refresh token could be used to issue
     * a new access token for a deleted user, which fails with a foreign key violation on
     * oauth_access_tokens.user_id. This removes the refresh tokens that were left behind and adds the
     * foreign key, so that refresh tokens are deleted together with their access token.
     */
    public function up(): void
    {
        DB::table('oauth_refresh_tokens')
            ->whereNotExists(function (Builder $query): void {
                $query->select('id')
                    ->from('oauth_access_tokens')
                    ->whereColumn('oauth_refresh_tokens.access_token_id', 'oauth_access_tokens.id');
            })
            ->delete();

        Schema::table('oauth_refresh_tokens', function (Blueprint $table): void {
            $table->foreign('access_token_id')
                ->references('id')
                ->on('oauth_access_tokens')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_refresh_tokens', function (Blueprint $table): void {
            $table->dropForeign(['access_token_id']);
        });
    }
};
