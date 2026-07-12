<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Extensions\Fortify\CustomLoginResponse;
use App\Extensions\Fortify\CustomTwoFactorLoginResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Dummy bcrypt hash compared against when no user matches the submitted
     * email. Hash::check is run against it so login takes the same time whether
     * or not the email exists — otherwise an unknown email would skip the
     * (deliberately slow) hash and return faster, letting an attacker enumerate
     * registered accounts by timing the response. The plaintext is irrelevant:
     * it is only ever checked against attacker-supplied input and never matches.
     */
    private const ABSENT_USER_PASSWORD_HASH = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    /**
     * Authorization rules applied AFTER the password is verified. Each rule
     * receives the authenticated user + request and returns whether the login
     * may proceed; any rule returning false denies it. This is an extension
     * point: modules (e.g. SSO enforcement) add a rule to veto a password login
     * instead of replacing this credential check — which would silently drift
     * from the host logic the next time it changes.
     *
     * @var array<int, \Closure(User, Request): bool>
     */
    protected static array $loginRules = [];

    /**
     * Authorization rules applied before a password reset is completed. Rules
     * receive the user being reset + submitted input and return whether the
     * local reset flow may set a new password for that account.
     *
     * @var array<int, \Closure(User, array<string, mixed>): bool>
     */
    protected static array $passwordResetRules = [];

    /**
     * Register an additional rule that gates password login (see $loginRules).
     *
     * @param  \Closure(User, Request): bool  $rule
     */
    public static function authenticateUsingRule(\Closure $rule): void
    {
        static::$loginRules[] = $rule;
    }

    /**
     * Register an additional rule that gates password reset completion.
     *
     * @param  \Closure(User, array<string, mixed>): bool  $rule
     */
    public static function resetPasswordUsingRule(\Closure $rule): void
    {
        static::$passwordResetRules[] = $rule;
    }

    /**
     * Check whether the given user may complete the local password reset flow.
     *
     * @param  array<string, mixed>  $input
     */
    public static function canResetPassword(User $user, array $input = []): bool
    {
        foreach (static::$passwordResetRules as $rule) {
            if (! $rule($user, $input)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::registerView(function () {
            return Inertia::render('Auth/Register', [
                'terms_url' => config('auth.terms_url'),
                'privacy_policy_url' => config('auth.privacy_policy_url'),
                'newsletter_consent' => config('auth.newsletter_consent'),
            ]);
        });

        Fortify::loginView(function () {
            return Inertia::render('Auth/Login', [
                'canResetPassword' => Route::has('password.request'),
                'status' => session('status'),
            ]);
        });

        Fortify::requestPasswordResetLinkView(function () {
            return Inertia::render('Auth/ForgotPassword', [
                'status' => session('status'),
            ]);
        });

        Fortify::resetPasswordView(function (Request $request) {
            return Inertia::render('Auth/ResetPassword', [
                'email' => $request->input('email'),
                'token' => $request->route('token'),
            ]);
        });

        Fortify::verifyEmailView(function () {
            return Inertia::render('Auth/VerifyEmail', [
                'status' => session('status'),
            ]);
        });

        Fortify::twoFactorChallengeView(function () {
            return Inertia::render('Auth/TwoFactorChallenge');
        });

        Fortify::confirmPasswordView(function () {
            return Inertia::render('Auth/ConfirmPassword');
        });

        Fortify::authenticateUsing(function (Request $request): ?User {
            /** @var User|null $user */
            $user = User::query()
                ->where('email', $request->email)
                ->where('is_placeholder', '=', false)
                ->first();

            // Always run the hash check — against the real hash, or a dummy when
            // there is no user — so login timing is identical either way (see
            // ABSENT_USER_PASSWORD_HASH). Passwordless accounts (SSO-only users
            // have password = null) fail here, so they cannot password-login.
            $existingPasswordHash = $user->password ?? self::ABSENT_USER_PASSWORD_HASH;

            $passwordIsValid = Hash::check((string) $request->password, $existingPasswordHash);

            if ($user !== null && $passwordIsValid) {
                // Credentials are valid; now apply any registered authorization
                // rules (e.g. SSO enforcement may still block password login).
                foreach (static::$loginRules as $rule) {
                    if (! $rule($user, $request)) {
                        return null;
                    }
                }

                return $user;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        $this->app->instance(LoginResponseContract::class, new CustomLoginResponse);
        $this->app->instance(TwoFactorLoginResponse::class, new CustomTwoFactorLoginResponse);
    }
}
