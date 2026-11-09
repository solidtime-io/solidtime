<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\User;
use App\Service\AppSessionService;
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
     * Log the user out of their other browser sessions and apps across all devices.
     */
    public function destroy(Request $request, StatefulGuard $guard, AppSessionService $appSessionService): RedirectResponse
    {
        $password = (string) $request->string('password');

        $confirmed = app(ConfirmPassword::class)($guard, $request->user(), $password);

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'password' => __('The password is incorrect.'),
            ]);
        }

        $guard->logoutOtherDevices($password);

        $this->deleteOtherSessionRecords($request);

        /** @var User $user */
        $user = $request->user();
        $appSessionService->revokeAppSessions($user);

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
