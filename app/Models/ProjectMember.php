<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectMemberRole;
use App\Models\Concerns\CustomAuditable;
use App\Models\Concerns\HasUuids;
use Database\Factories\ProjectMemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property int|null $billable_rate
 * @property string $project_id Project ID
 * @property string $member_id Member ID
 * @property string $user_id User ID (legacy)
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property ProjectMemberRole $role
 * @property-read Project $project
 * @property-read Member $member
 * @property-read User $user
 *
 * @method static Builder<ProjectMember> whereBelongsToOrganization(Organization $organization)
 * @method static ProjectMemberFactory factory()
 */
class ProjectMember extends Model implements AuditableContract
{
    use CustomAuditable;

    /** @use HasFactory<ProjectMemberFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'billable_rate' => 'int',
        'role' => ProjectMemberRole::class,
    ];

    /**
     * @return BelongsTo<Project, ProjectMember>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * @deprecated Use member relationship instead
     *
     * @return BelongsTo<User, ProjectMember>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Member, ProjectMember>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * @param  Builder<ProjectMember>  $builder
     */
    public function scopeWhereBelongsToOrganization(Builder $builder, Organization $organization): void
    {
        $builder->whereHas('project', static function (Builder $query) use ($organization): void {
            $query->whereBelongsTo($organization, 'organization');
        });
    }
}
