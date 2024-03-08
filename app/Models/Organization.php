<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

/**
 * @property string $id
 * @property string $name
 * @property bool $personal_team
 * @property User $owner
 * @property Collection<User> $users
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
     * Get all the non-placeholder users of the organization including its owner.
     *
     * @return Collection<User>
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
     * @return BelongsToMany<User>
     */
    public function realUsers(): BelongsToMany
    {
        return $this->users()
            ->where('is_placeholder', false);
    }
}
