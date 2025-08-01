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
        DB::table('oauth_clients')->update(['provider' => 'users']); // Change default provider if necessary

        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->text('grant_types')->default('[]')->after('provider');
            $table->text('redirect_uris')->default('[]');
            $table->renameColumn('user_id', 'owner_id');
            $table->string('owner_type')->after('owner_id')->nullable();
        });

        DB::table('oauth_clients')
            ->where('redirect', '=', 'http://localhost')
            ->where('personal_access_client', '=', true)
            ->update(['redirect' => '']);

        DB::table('oauth_clients')
            ->whereNotNull('owner_id')
            ->update(['owner_type' => 'user']); // Value might be class name of the owner model, depends on if you use "enforceMorphMap"

        DB::table('oauth_clients')->eachById(function ($client): void {
            $grantTypes = ['urn:ietf:params:oauth:grant-type:device_code', 'refresh_token'];
            $confidential = ! empty($client->secret);
            $noRedirect = empty($client->redirect);
            $redirectUris = $noRedirect ? [] : [$client->redirect];
            $firstParty = empty($client->owner_id);

            if (! $noRedirect) {
                $grantTypes[] = 'authorization_code';
                $grantTypes[] = 'implicit';
            }

            if ($confidential && $firstParty) {
                $grantTypes[] = 'client_credentials';
            }

            if ($client->personal_access_client && $confidential) {
                $grantTypes[] = 'personal_access';
            }

            if ($client->password_client) {
                $grantTypes[] = 'password';
            }

            DB::table('oauth_clients')
                ->where('id', $client->id)
                ->update([
                    'redirect_uris' => $redirectUris,
                    'grant_types' => $grantTypes,
                ]);
        });

        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->index(['owner_id', 'owner_type']);
            $table->dropColumn('redirect');
            $table->dropColumn('personal_access_client');
            $table->dropColumn('password_client');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->dropIndex(['owner_id', 'owner_type']);
            $table->renameColumn('owner_id', 'user_id');
            $table->foreign('user_id')
                ->on('users')
                ->references('id')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('redirect')->nullable();
            $table->boolean('personal_access_client')->default(false);
            $table->boolean('password_client')->default(false);
        });

        DB::table('oauth_clients')->eachById(function ($client): void {
            $redirectUris = json_decode($client->redirect_uris);
            $grantTypes = json_decode($client->grant_types);

            DB::table('oauth_clients')
                ->where('id', $client->id)
                ->update([
                    'redirect' => $redirectUris[0] ?? '', // redirect not nullable
                    'password_client' => in_array('password', $grantTypes, true)
                        && in_array('refresh_token', $grantTypes, true),
                    'personal_access_client' => in_array('personal_access', $grantTypes, true),
                ]);
        });

        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->dropColumn(['grant_types', 'redirect_uris', 'owner_type']);
            $table->string('redirect')->nullable(false)->change();
            $table->boolean('personal_access_client')->default(null)->change();
            $table->boolean('password_client')->default(null)->change();
        });

    }
};
