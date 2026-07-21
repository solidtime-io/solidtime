<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\Weekday;
use App\Models\User;
use App\Service\IpLookup\IpLookupServiceContract;
use App\Service\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        $this->ensureEnabled();

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, UserService $userService): RedirectResponse
    {
        $this->ensureEnabled();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            Log::debug('Google SSO callback failed', ['message' => $exception->getMessage()]);

            return $this->failure(__('Google sign-in failed. Please try again.'));
        }

        $googleId = (string) $googleUser->getId();
        $email = strtolower((string) $googleUser->getEmail());
        if ($googleId === '' || $email === '') {
            return $this->failure(__('Google did not provide the information needed to sign you in.'));
        }

        if (! $this->isDomainAllowed($email)) {
            return $this->failure(__('This Google account is not allowed to sign in here.'));
        }

        /** @var User|null $user */
        $user = User::query()
            ->where('google_id', '=', $googleId)
            ->where('is_placeholder', '=', false)
            ->first();

        if ($user === null) {
            /** @var User|null $user */
            $user = User::query()
                ->where('email', '=', $email)
                ->where('is_placeholder', '=', false)
                ->first();

            if ($user !== null && $user->google_id !== null) {
                return $this->failure(__('This email address is already linked to a different Google account.'));
            }
        }

        if ($user === null) {
            if (! config('services.google.sso_auto_provision')) {
                return $this->failure(__('There is no account for this Google account. Please ask an administrator to invite you.'));
            }

            $user = $this->provisionUser($request, $userService, $googleUser->getName() ?? $email, $email);
        }

        $user->google_id = $googleId;
        if ($user->email_verified_at === null) {
            $user->email_verified_at = Carbon::now();
        }
        $user->save();

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function provisionUser(Request $request, UserService $userService, string $name, string $email): User
    {
        $ipLookupResponse = app(IpLookupServiceContract::class)->lookup($request->ip());

        $user = null;
        DB::transaction(function () use (&$user, $userService, $name, $email, $ipLookupResponse): void {
            $user = $userService->createUser(
                $name,
                $email,
                null,
                $ipLookupResponse->timezone ?? 'UTC',
                $ipLookupResponse->startOfWeek ?? Weekday::Monday,
                $ipLookupResponse?->currency,
                verifyEmail: true,
            );
        });

        return $user;
    }

    private function isDomainAllowed(string $email): bool
    {
        $allowedDomains = config('services.google.sso_allowed_domains');
        if ($allowedDomains === null || trim((string) $allowedDomains) === '') {
            return true;
        }

        $domain = Str::lower(Str::afterLast($email, '@'));
        foreach (explode(',', (string) $allowedDomains) as $allowedDomain) {
            if ($domain === Str::lower(trim($allowedDomain))) {
                return true;
            }
        }

        return false;
    }

    private function ensureEnabled(): void
    {
        if (! config('services.google.sso_enabled') || config('services.google.client_id') === null) {
            abort(404);
        }
    }

    private function failure(string $message): RedirectResponse
    {
        return redirect(route('login'))
            ->with('bannerText', $message)
            ->with('bannerStyle', 'danger');
    }
}
