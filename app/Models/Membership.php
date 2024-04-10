<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Jetstream\Membership as JetstreamMembership;

/**
 * @property string $id
 * @property string $role
 * @property int|null $billable_rate
 * @property string $organization_id
 * @property string $user_id
 * @property string $created_at
 * @property string $updated_at
 * @property-read Organization $organization
 * @property-read User $user
 *
 * @method static MembershipFactory factory()
 */
class Membership extends JetstreamMembership
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the pivot model.
     *
     * @var string
     */
    protected $table = 'organization_user';

    /**
     * @return BelongsTo<User, Membership>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Organization, Membership>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}
