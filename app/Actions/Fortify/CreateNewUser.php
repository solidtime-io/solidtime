<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Enums\Weekday;
use App\Events\NewsletterRegistered;
use App\Models\User;
use App\Service\IpLookup\IpLookupServiceContract;
use App\Service\TimezoneService;
use App\Service\UserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use Log;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        if (! config('app.enable_registration')) {
            throw ValidationException::withMessages([
                'email' => [__('Registration is disabled.')],
            ]);
        }

        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,strict',
                'max:255',
                UniqueEloquent::make(User::class, 'email', function (Builder $builder): Builder {
                    /** @var Builder<User> $builder */
                    return $builder->where('is_placeholder', '=', false);
                }),
            ],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
            'newsletter_consent' => [
                'boolean',
            ],
        ])->validate();

        $timezone = null;
        if (array_key_exists('timezone', $input) && is_string($input['timezone'])) {
            if (app(TimezoneService::class)->isValid($input['timezone'])) {
                $timezone = $input['timezone'];
            } else {
                $timezone = app(TimezoneService::class)->mapLegacyTimezone($input['timezone']);
                if ($timezone === null) {
                    Log::debug('Invalid timezone', ['timezone' => $input['timezone']]);
                }
            }
        }

        $ipLookupResponse = app(IpLookupServiceContract::class)->lookup(request()->ip());

        $startOfWeek = Weekday::Monday;
        $numberFormat = null;
        $currencyFormat = null;
        $dateFormat = null;
        $intervalFormat = null;
        $timeFormat = null;
        $currency = null;
        if ($ipLookupResponse !== null) {
            $startOfWeek = $ipLookupResponse->startOfWeek ?? Weekday::Monday;
            if ($timezone === null) {
                $timezone = $ipLookupResponse->timezone;
            }
            $currency = $ipLookupResponse->currency;
        }
        $user = null;
        DB::transaction(function () use (&$user, $input, $timezone, $startOfWeek, $currency, $numberFormat, $currencyFormat, $dateFormat, $intervalFormat, $timeFormat): void {
            $userService = app(UserService::class);
            $user = $userService->createUser(
                $input['name'],
                $input['email'],
                $input['password'],
                $timezone ?? 'UTC',
                $startOfWeek,
                $currency,
                $numberFormat,
                $currencyFormat,
                $dateFormat,
                $intervalFormat,
                $timeFormat
            );
        });

        $newsletterConsent = isset($input['newsletter_consent']) && (bool) $input['newsletter_consent'];
        if ($newsletterConsent) {
            NewsletterRegistered::dispatch($input['name'], $input['email'], $user->getKey());
        }

        return $user;
    }
}
