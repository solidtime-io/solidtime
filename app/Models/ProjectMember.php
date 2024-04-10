<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProjectMemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int|null $billable_rate
 * @property string $project_id
 * @property string $user_id
 * @property-read Project $project
 * @property-read User $user
 *
 * @method static Builder<ProjectMember> whereBelongsToOrganization(Organization $organization)
 * @method static ProjectMemberFactory factory()
 */
class ProjectMember extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'billable_rate' => 'int',
    ];

    /**
     * @return BelongsTo<Project, ProjectMember>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * @return BelongsTo<User, ProjectMember>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
