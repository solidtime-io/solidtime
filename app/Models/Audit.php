<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Models\Audit as PackageAuditModel;

/**
 * @property int $id
 * @property string|null $actor_type
 * @property string|null $actor_id
 * @property string $event
 * @property string $auditable_type
 * @property string $auditable_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $url
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $tags
 * @property string|null $owner_user_id
 * @property string|null $owner_organization_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $ownerUser
 * @property-read Organization|null $ownerOrganization
 *
 * @method static AuditFactory factory()
 */
class Audit extends PackageAuditModel
{
    /** @use HasFactory<AuditFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owner_organization_id');
    }
}
