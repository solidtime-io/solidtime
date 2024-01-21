<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\TeamInvitation as JetstreamTeamInvitation;

/**
 * @property string $id
 * @property string $email
 */
class OrganizationInvitation extends JetstreamTeamInvitation
{
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
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Jetstream::teamModel(), 'organization_id');
    }

    /**
     * Get the organization that the invitation belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Jetstream::teamModel(), 'organization_id');
    }
}
