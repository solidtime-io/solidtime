<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\User;
use App\Service\IdentityConfirmationService;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmPassword;

class OtherBrowserSessionsController extends Controller
{
    /**
     * Log the user out of their other browser sessions across all devices.
     */
    public function destroy(Request $request, StatefulGuard $guard, IdentityConfirmationService $identityConfirmationService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Users who can't confirm with a password (e.g. SSO) may log out other sessions without confirming.
        // Someone with a stolen session could only end the user's other sessions, while asking for a
        // confirmation would keep the user from ending the stolen one.
        if (! $identityConfirmationService->confirmsWithPassword($user)) {
            // Without a password to rehash, other sessions can only be removed from the database
            if (config('session.driver') !== 'database') {
                throw ValidationException::withMessages([
                    'password' => __('Signing out other sessions is unavailable on this instance. Contact your administrator.'),
                ]);
            }

            $this->deleteOtherSessionRecords($request);

            return back(303);
        }

        $password = (string) $request->string('password');

        $confirmed = app(ConfirmPassword::class)($guard, $request->user(), $password);

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'password' => __('The password is incorrect.'),
            ]);
        }

        $guard->logoutOtherDevices($password);

        $this->deleteOtherSessionRecords($request);

        return back(303);
    }

    /**
     * Delete the other browser session records from storage.
     */
    protected function deleteOtherSessionRecords(Request $request): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
