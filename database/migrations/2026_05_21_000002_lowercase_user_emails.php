<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @throws RuntimeException
     */
    public function up(): void
    {
        $duplicateEmails = DB::table('users')
            ->selectRaw('LOWER(email) as normalized_email')
            ->selectRaw('COUNT(*) as user_count')
            ->selectRaw("STRING_AGG(id::text || ' <' || email || '>', ', ' ORDER BY email) as users")
            ->where('is_placeholder', false)
            ->groupByRaw('LOWER(email)')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('normalized_email')
            ->get();

        if ($duplicateEmails->isNotEmpty()) {
            $duplicateEmailMessage = $duplicateEmails
                ->take(20)
                ->map(fn (\stdClass $duplicateEmail): string => sprintf(
                    '%s (%d users: %s)',
                    $duplicateEmail->normalized_email,
                    $duplicateEmail->user_count,
                    $duplicateEmail->users,
                ))
                ->implode('; ');

            $remainingDuplicateCount = $duplicateEmails->count() - 20;
            $remainingDuplicateMessage = $remainingDuplicateCount > 0
                ? sprintf('; and %d more duplicate normalized emails', $remainingDuplicateCount)
                : '';

            throw new RuntimeException(
                'Cannot lowercase users.email because doing so would create duplicate non-placeholder user emails and violate the unique index on users.email for non-placeholder users. Resolve these case-insensitive duplicates first: '.
                $duplicateEmailMessage.
                $remainingDuplicateMessage
            );
        }

        DB::table('users')
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
