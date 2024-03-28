<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Enums\Weekday;
use App\Models\Organization;
use App\Models\User;
use App\Service\TimezoneService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

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
        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                new UniqueEloquent(User::class, 'email', function (Builder $builder): Builder {
                    /** @var Builder<User> $builder */
                    return $builder->where('is_placeholder', '=', false);
                }),
            ],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        $timezone = 'UTC';
        if (array_key_exists('timezone', $input) && is_string($input['timezone']) && app(TimezoneService::class)->isValid($input['timezone'])) {
            $timezone = $input['timezone'];
        }

        return DB::transaction(function () use ($input, $timezone) {
            return tap(User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'timezone' => $timezone,
                'week_start' => Weekday::Monday,
            ]), function (User $user) {
                $this->createTeam($user);
            });
        });
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Organization::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Organization",
            'personal_team' => true,
        ]));
    }
}
