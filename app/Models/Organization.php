<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CustomAuditable;
use App\Models\Concerns\HasUuids;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Team as JetstreamTeam;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $name
 * @property bool $personal_team
 * @property string $currency
 * @property int|null $billable_rate
 * @property string $user_id
 * @property bool $employees_can_see_billable_rates
 * @property User $owner
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, User> $users
 * @property Collection<int, User> $realUsers
 * @property-read Collection<int, OrganizationInvitation> $teamInvitations
 * @property Member $membership
 *
 * @method HasMany<OrganizationInvitation> teamInvitations()
 * @method static OrganizationFactory factory()
 */
class Organization extends JetstreamTeam implements AuditableContract
{
    use CustomAuditable;

    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'string',
        'personal_team' => 'boolean',
        'currency' => 'string',
        'employees_can_see_billable_rates' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_team',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'EUR',
    ];

    /**
     * Get all the non-placeholder users of the organization including its owner.
     *
     * @return Collection<int, User>
     */
    public function allRealUsers(): Collection
    {
        return $this->realUsers->merge([$this->owner]);
    }

    public function hasRealUserWithEmail(string $email): bool
    {
        return $this->allRealUsers()->contains(function (User $user) use ($email): bool {
            return $user->email === $email;
        });
    }

    /**
     * Get all the users that belong to the team.
     *
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, Member::class)
            ->withPivot([
                'id',
                'role',
                'billable_rate',
            ])
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * Get the owner of the team.
     *
     * @return BelongsTo<User, Organization>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Member>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * @return BelongsToMany<User>
     */
    public function realUsers(): BelongsToMany
    {
        return $this->users()
            ->where('is_placeholder', false);
    }

    /**
     * This method prevents an unhandled exception when the ID is not a UUID.
     * Normally this can be fixed with a route pattern, but Jetstream does not use route model binding.
     *
     * @param  array<string>  $columns
     */
    public function findOrFail(string $id, array $columns = ['*']): \Laravel\Jetstream\Team
    {
        if (! Str::isUuid($id)) {
            throw (new ModelNotFoundException)->setModel(
                self::class, $id
            );
        }

        return parent::findOrFail($id, $columns);
    }
}
