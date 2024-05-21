<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Database\Factories\MemberFactory;
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
 * @method static MemberFactory factory()
 */
class Member extends JetstreamMembership
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the pivot model.
     *
     * @var string
     */
    protected $table = 'members';

    /**
     * @return BelongsTo<User, Member>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Organization, Member>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}
