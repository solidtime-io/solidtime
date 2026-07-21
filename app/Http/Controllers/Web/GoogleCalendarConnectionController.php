<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\GoogleCalendarConnection;
use App\Service\GoogleCalendar\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleCalendarConnectionController extends Controller
{
    public function connect(Request $request): SymfonyRedirectResponse
    {
        $this->ensureConfigured();

        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google');

        return $driver
            ->scopes(['https://www.googleapis.com/auth/calendar.events.readonly'])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'login_hint' => $request->user()?->email,
            ])
            ->redirectUrl(route('google-calendar.callback'))
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $this->ensureConfigured();

        try {
            /** @var GoogleProvider $driver */
            $driver = Socialite::driver('google');
            /** @var User $googleUser */
            $googleUser = $driver
                ->redirectUrl(route('google-calendar.callback'))
                ->user();
        } catch (Throwable $exception) {
            Log::debug('Google Calendar connect callback failed', ['message' => $exception->getMessage()]);

            return $this->failure(__('Connecting your Google Calendar failed. Please try again.'));
        }

        $email = strtolower((string) $googleUser->getEmail());
        if ($email === '') {
            return $this->failure(__('Google did not provide the information needed to connect your calendar.'));
        }

        /** @var GoogleCalendarConnection|null $existingConnection */
        $existingConnection = GoogleCalendarConnection::query()
            ->where('user_id', '=', $request->user()->getKey())
            ->first();

        // Socialite's phpdoc types refreshToken as string, but it is null when Google does not send one.
        $refreshToken = $this->nonEmptyStringOrNull($googleUser->refreshToken);
        if ($refreshToken === null && $existingConnection !== null && strtolower($existingConnection->google_email) === $email) {
            // Google only issues a refresh token on first consent; keep the stored one when reconnecting the same account.
            $refreshToken = $existingConnection->refresh_token;
        }
        if ($refreshToken === null) {
            return $this->failure(__('Google did not grant offline calendar access. Please try again.'));
        }

        GoogleCalendarConnection::query()->updateOrCreate([
            'user_id' => $request->user()->getKey(),
        ], [
            'google_email' => $email,
            'refresh_token' => $refreshToken,
            'access_token' => $googleUser->token,
            'access_token_expires_at' => $googleUser->expiresIn !== null
                ? Carbon::now()->addSeconds($googleUser->expiresIn)
                : null,
        ]);

        return redirect(route('calendar'))
            ->with('bannerText', __('Your Google Calendar is now connected.'))
            ->with('bannerStyle', 'success');
    }

    public function disconnect(Request $request, GoogleCalendarService $googleCalendarService): RedirectResponse
    {
        /** @var GoogleCalendarConnection|null $connection */
        $connection = GoogleCalendarConnection::query()
            ->where('user_id', '=', $request->user()->getKey())
            ->first();

        if ($connection !== null) {
            $googleCalendarService->revoke($connection);
            $connection->delete();
        }

        return redirect(route('profile.show'))
            ->with('bannerText', __('Your Google Calendar has been disconnected.'))
            ->with('bannerStyle', 'success');
    }

    private function nonEmptyStringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function ensureConfigured(): void
    {
        if (config('services.google.client_id') === null) {
            abort(404);
        }
    }

    private function failure(string $message): RedirectResponse
    {
        return redirect(route('profile.show'))
            ->with('bannerText', $message)
            ->with('bannerStyle', 'danger');
    }
}
