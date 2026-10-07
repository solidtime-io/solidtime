<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditableWithoutOwner;
use Database\Factories\AuditFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
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
 * @method static Builder<Audit> whereMissingOwner()
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

    /**
     * Whether audits of the given auditable type intentionally have no owner (see AuditableWithoutOwner).
     */
    public static function isAuditableTypeWithoutOwner(string $auditableType): bool
    {
        $modelClass = Relation::getMorphedModel($auditableType) ?? $auditableType;

        return is_subclass_of($modelClass, AuditableWithoutOwner::class);
    }

    /**
     * Auditable types (morph aliases) whose audits intentionally have no owner (see AuditableWithoutOwner).
     *
     * @return array<int, string>
     */
    public static function getAuditableTypesWithoutOwner(): array
    {
        return collect(Relation::morphMap())
            ->filter(fn (string $modelClass): bool => is_subclass_of($modelClass, AuditableWithoutOwner::class))
            ->keys()
            ->values()
            ->all();
    }

    /**
     * Audits that have neither an owner organization nor an owner user, although their auditable type should have one.
     * These are audits whose owner no longer exists or could not be determined (yet).
     *
     * @param  Builder<Audit>  $builder
     */
    public function scopeWhereMissingOwner(Builder $builder): void
    {
        $builder->whereNull('owner_organization_id')
            ->whereNull('owner_user_id')
            ->whereNotIn('auditable_type', self::getAuditableTypesWithoutOwner());
    }
}
