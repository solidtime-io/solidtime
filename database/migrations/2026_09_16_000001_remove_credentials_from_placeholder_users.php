<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Placeholder users used to be created as a full copy of the user they were made from,
     * which included the credentials and the account state of that user. A placeholder is a
     * stand-in for a person in one organization, not an account, and the row shares the email
     * address with the real account, so these values are removed from the placeholders that
     * already exist. The organization a placeholder belongs to is recorded on its member row.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('is_placeholder', '=', true)
            ->where(function (Builder $builder): void {
                $builder->whereNotNull('password')
                    ->orWhereNotNull('remember_token')
                    ->orWhereNotNull('two_factor_secret')
                    ->orWhereNotNull('two_factor_recovery_codes')
                    ->orWhereNotNull('two_factor_confirmed_at')
                    ->orWhereNotNull('email_verified_at')
                    ->orWhereNotNull('pending_email')
                    ->orWhereNotNull('current_team_id')
                    ->orWhereNotNull('profile_photo_path');
            })
            ->update([
                'password' => null,
                'remember_token' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'email_verified_at' => null,
                'pending_email' => null,
                'current_team_id' => null,
                'profile_photo_path' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
