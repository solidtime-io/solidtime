<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Database\Factories\OrganizationInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\TeamInvitation as JetstreamTeamInvitation;

/**
 * @property string $id
 * @property string $email
 * @property string $role
 * @property string $organization_id
 * @property Carbon|null $updated_at
 * @property Carbon|null $created_at
 * @property-read Organization $organization
 *
 * @method static OrganizationInvitationFactory factory()
 */
class OrganizationInvitation extends JetstreamTeamInvitation
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'organization_invitations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'role',
    ];

    /**
     * Get the organization that the invitation belongs to.
     *
     * @return BelongsTo<Organization, OrganizationInvitation>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Jetstream::teamModel(), 'organization_id');
    }

    /**
     * Get the organization that the invitation belongs to.
     *
     * @return BelongsTo<Organization, OrganizationInvitation>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Jetstream::teamModel(), 'organization_id');
    }
}
