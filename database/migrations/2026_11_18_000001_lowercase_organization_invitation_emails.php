<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Invitations used to be stored with the email as entered, while user emails are stored lowercase.
     * This lowercases the existing invitation emails. Invitations of the same organization whose emails
     * only differ in case would violate the unique index on (organization_id, email) after lowercasing,
     * so only one of them is kept: an accepted invitation is preferred, otherwise the newest one.
     *
     * It also lowercases placeholder user emails. Imports used to store them as written in the source file,
     * while imports now look them up lowercased. Placeholders are excluded from the unique index on
     * users.email, so this can not fail on duplicates.
     */
    public function up(): void
    {
        DB::statement('
            DELETE FROM organization_invitations
            WHERE id IN (
                SELECT id FROM (
                    SELECT
                        id,
                        ROW_NUMBER() OVER (
                            PARTITION BY organization_id, LOWER(email)
                            ORDER BY (accepted_at IS NULL), created_at DESC NULLS LAST, id
                        ) AS row_number
                    FROM organization_invitations
                ) AS ranked_invitations
                WHERE row_number > 1
            )
        ');

        DB::table('organization_invitations')
            ->whereRaw('email <> LOWER(email)')
            ->update([
                'email' => DB::raw('LOWER(email)'),
            ]);

        DB::table('users')
            ->where('is_placeholder', '=', true)
            ->whereRaw('email <> LOWER(email)')
            ->update([
                'email' => DB::raw('LOWER(email)'),
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
