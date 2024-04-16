<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Weekday;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Passport\HasApiTokens;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string|null $password
 * @property string $timezone
 * @property bool $is_placeholder
 * @property Weekday $week_start
 * @property string|null $profile_photo_path
 * @property-read Organization $currentTeam
 * @property-read string $profile_photo_url
 * @property Collection<Organization> $organizations
 * @property Collection<TimeEntry> $timeEntries
 *
 * @method HasMany<Organization> ownedTeams()
 * @method static UserFactory factory()
 * @method static Builder<User> query()
 * @method Builder<User> belongsToOrganization(Organization $organization)
 * @method Builder<User> active()
 */
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use HasUuids;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'string',
        'email' => 'string',
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_placeholder' => 'boolean',
        'week_start' => Weekday::class,
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'week_start' => Weekday::Monday,
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->email, config('auth.super_admins', []), true) && $this->hasVerifiedEmail();
    }

    /**
     * @return BelongsToMany<Organization>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, Membership::class)
            ->withPivot([
                'id',
                'role',
                'billable_rate',
            ])
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * @return HasMany<TimeEntry>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * @param  Builder<User>  $builder
     */
    public function scopeActive(Builder $builder): void
    {
        $builder->where('is_placeholder', '=', false);
    }

    /**
     * @param  Builder<User>  $builder
     * @return Builder<User>
     */
    public function scopeBelongsToOrganization(Builder $builder, Organization $organization): Builder
    {
        return $builder->where(function (Builder $builder) use ($organization): Builder {
            return $builder->whereHas('organizations', function (Builder $query) use ($organization): void {
                $query->whereKey($organization->getKey());
            })->orWhereHas('ownedTeams', function (Builder $query) use ($organization): void {
                $query->whereKey($organization->getKey());
            });
        });
    }
}
