<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\Weekday;
use App\Service\Dto\UserAgentDto;
use App\Service\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;

class UserProfileController extends Controller
{
    /**
     * Validate the two-factor authentication state for the request.
     */
    protected function validateTwoFactorAuthenticationState(Request $request): void
    {
        if (! Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm')) {
            return;
        }

        $currentTime = time();

        // Notate totally disabled state in session...
        if ($this->twoFactorAuthenticationDisabled($request)) {
            $request->session()->put('two_factor_empty_at', $currentTime);
        }

        // If was previously totally disabled this session but is now confirming, notate time...
        if ($this->hasJustBegunConfirmingTwoFactorAuthentication($request)) {
            $request->session()->put('two_factor_confirming_at', $currentTime);
        }

        // If the profile is reloaded and is not confirmed but was previously in confirming state, disable...
        if ($this->neverFinishedConfirmingTwoFactorAuthentication($request, $currentTime)) {
            app(DisableTwoFactorAuthentication::class)(Auth::user());

            $request->session()->put('two_factor_empty_at', $currentTime);
            $request->session()->remove('two_factor_confirming_at');
        }
    }

    /**
     * Determine if two-factor authentication is totally disabled.
     *
     * @return bool
     */
    protected function twoFactorAuthenticationDisabled(Request $request)
    {
        return is_null($request->user()->two_factor_secret) &&
            is_null($request->user()->two_factor_confirmed_at);
    }

    /**
     * Determine if two-factor authentication is just now being confirmed within the last request cycle.
     *
     * @return bool
     */
    protected function hasJustBegunConfirmingTwoFactorAuthentication(Request $request)
    {
        return ! is_null($request->user()->two_factor_secret) &&
            is_null($request->user()->two_factor_confirmed_at) &&
            $request->session()->has('two_factor_empty_at') &&
            is_null($request->session()->get('two_factor_confirming_at'));
    }

    /**
     * Determine if two-factor authentication was never totally confirmed once confirmation started.
     *
     * @return bool
     */
    protected function neverFinishedConfirmingTwoFactorAuthentication(Request $request, int $currentTime)
    {
        return ! array_key_exists('code', $request->session()->getOldInput()) &&
            is_null($request->user()->two_factor_confirmed_at) &&
            $request->session()->get('two_factor_confirming_at', 0) !== $currentTime;
    }

    /**
     * Show the general profile settings screen.
     */
    public function show(Request $request): Response
    {
        $this->validateTwoFactorAuthenticationState($request);

        return Inertia::render('Profile/Show', [
            'timezones' => app(TimezoneService::class)->getSelectOptions(),
            'weekdays' => Weekday::toSelectArray(),
            'confirmsTwoFactorAuthentication' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
            'sessions' => $this->sessions($request),
        ]);
    }

    /**
     * Get the current sessions.
     *
     * @return array<int, object{agent: array{is_desktop: bool, platform: string|null, browser: string|null}, ip_address: string, is_current_device: bool, last_active: string}&\stdClass>
     */
    public function sessions(Request $request): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return collect(
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->getAuthIdentifier())
                ->orderBy('last_activity', 'desc')
                ->get()
        )->map(function (object $session) use ($request): object {
            $agent = $this->createAgent(is_string($session->user_agent) ? $session->user_agent : '');

            return (object) [
                'agent' => [
                    'is_desktop' => $agent->isDesktop(),
                    'platform' => $agent->platform(),
                    'browser' => $agent->browser(),
                ],
                'ip_address' => is_string($session->ip_address) ? $session->ip_address : '',
                'is_current_device' => $session->id === $request->session()->getId(),
                'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
            ];
        })->all();
    }

    /**
     * Create a new agent instance from the given session.
     */
    protected function createAgent(string $userAgent): UserAgentDto
    {
        return tap(new UserAgentDto, fn ($agent) => $agent->setUserAgent($userAgent));
    }
}
