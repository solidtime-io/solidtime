<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $project_id
 * @property string $organization_id
 * @property Carbon|null $done_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read Organization $organization
 * @property-read Collection<int, TimeEntry> $timeEntries
 * @property-read bool $is_done
 *
 * @method static TaskFactory factory()
 */
class Task extends Model
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
        'done_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Project, Task>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Organization, Task>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return HasMany<TimeEntry>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'task_id');
    }

    /**
     * @param  Builder<Task>  $builder
     * @return Builder<Task>
     */
    public function scopeVisibleByEmployee(Builder $builder, User $user): Builder
    {
        return $builder->whereHas('project', function (Builder $builder) use ($user): Builder {
            /** @var Builder<Project> $builder */
            return $builder->visibleByEmployee($user);
        });
    }

    /**
     * @return Attribute<bool, never>
     */
    public function isDone(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => isset($attributes['done_at']),
        );
    }
}
