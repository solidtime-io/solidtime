<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use App\Enums\Weekday;
use App\Models\Concerns\CustomAuditable;
use App\Models\Concerns\HasUuids;
use App\Models\Passport\Token;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passport\AuthCode;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string|null $pending_email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $two_factor_secret
 * @property string $timezone
 * @property bool $is_placeholder
 * @property Weekday $week_start
 * @property string|null $profile_photo_path
 * @property-read Organization|null $currentOrganization
 * @property-read string $profile_photo_url
 * @property-read Collection<int, Token> $tokens
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $current_team_id
 * @property Collection<int, Organization> $organizations
 * @property Collection<int, Organization> $ownedOrganizations
 * @property Collection<int, TimeEntry> $timeEntries
 * @property Member $membership
 *
 * @method HasMany<Organization, $this> ownedTeams()
 * @method static UserFactory factory()
 * @method static Builder<User> query()
 * @method Builder<User> belongsToOrganization(Organization $organization)
 * @method Builder<User> active()
 */
class User extends Authenticatable implements AuditableContract, FilamentUser, MustVerifyEmail, OAuthenticatable
{
    use CustomAuditable;
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasUuids;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
        'pending_email' => 'string',
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

    /**
     * Get the URL to the user's profile photo.
     *
     * @return Attribute<string, never>
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            return $this->profile_photo_path
                ? Storage::disk(config('filesystems.public'))->url($this->profile_photo_path)
                : $this->defaultProfilePhotoUrl();
        });
    }

    /**
     * Get the default profile photo URL if no profile photo has been uploaded.
     */
    protected function defaultProfilePhotoUrl(): string
    {
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->email, config('auth.super_admins', []), true) && $this->hasVerifiedEmail();
    }

    public function isMemberOfOrganization(Organization $organization): bool
    {
        if ($this->relationLoaded('organizations')) {
            return $this->organizations->contains(function (Organization $o) use ($organization): bool {
                return $o->getKey() === $organization->getKey();
            });
        }

        return $this->organizations()->whereKey($organization->getKey())->exists();
    }

    public function canBeImpersonated(): bool
    {
        return $this->is_placeholder === false;
    }

    /**
     * @return BelongsToMany<Organization, $this, Pivot, 'membership'>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, Member::class)
            ->withPivot([
                'id',
                'role',
                'billable_rate',
            ])
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * @return BelongsToMany<Organization, $this, Pivot, 'membership'>
     */
    public function ownedOrganizations(): BelongsToMany
    {
        return $this->organizations()->wherePivot('role', Role::Owner->value);
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'current_team_id');
    }

    /**
     * @return HasMany<ProjectMember, $this>
     */
    public function projectMembers(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'user_id');
    }

    /**
     * @return HasMany<Token, $this>
     */
    public function accessTokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    /**
     * @return HasMany<AuthCode, $this>
     */
    public function authCodes(): HasMany
    {
        return $this->hasMany(AuthCode::class);
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
        return $builder->whereHas('organizations', function (Builder $query) use ($organization): void {
            $query->whereKey($organization->getKey());
        });
    }
}
