<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Panel;
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
 * @property Collection<Organization> $organizations
 * @property Collection<TimeEntry> $timeEntries
 *
 * @method HasMany<Organization> ownedTeams()
 * @method static UserFactory factory()
 * @method static Builder<User> query()
 * @method Builder<User> belongsToOrganization(Organization $organization)
 */
class User extends Authenticatable
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
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->email, config('auth.super_admins', []), true);
    }

    /**
     * @return BelongsToMany<Organization>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, Membership::class)
            ->withPivot([
                'role',
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
