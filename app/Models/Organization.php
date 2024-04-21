<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Team as JetstreamTeam;

/**
 * @property string $id
 * @property string $name
 * @property bool $personal_team
 * @property string $currency
 * @property int|null $billable_rate
 * @property string $user_id
 * @property User $owner
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, User> $users
 * @property Collection<int, User> $realUsers
 * @property-read Collection<int, OrganizationInvitation> $teamInvitations
 * @property Membership $membership
 *
 * @method HasMany<OrganizationInvitation> teamInvitations()
 * @method static OrganizationFactory factory()
 */
class Organization extends JetstreamTeam
{
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
        return $this->belongsToMany(Jetstream::userModel(), Jetstream::membershipModel())
            ->withPivot([
                'id',
                'role',
                'billable_rate',
            ])
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * @return BelongsToMany<User>
     */
    public function realUsers(): BelongsToMany
    {
        return $this->users()
            ->where('is_placeholder', false);
    }
}
