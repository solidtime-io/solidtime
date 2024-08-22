<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Database\Factories\ProjectFactory;
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
 * @property string $color
 * @property string $organization_id
 * @property string $client_id
 * @property int|null $billable_rate
 * @property bool $is_public
 * @property bool $is_billable
 * @property-read bool $is_archived
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read Client|null $client
 * @property-read Collection<int, Task> $tasks
 * @property-read Collection<int, ProjectMember> $members
 *
 * @method Builder<Project> visibleByEmployee(User $user)
 * @method static ProjectFactory factory()
 */
class Project extends Model
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
        'color' => 'string',
    ];

    /**
     * Set default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_billable' => false,
    ];

    /**
     * @return BelongsTo<Organization, Project>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return BelongsTo<Client, Project>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * @return HasMany<ProjectMember>
     */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'project_id');
    }

    /**
     * @return HasMany<Task>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<TimeEntry>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'project_id');
    }

    /**
     * @param  Builder<Project>  $builder
     */
    public function scopeVisibleByEmployee(Builder $builder, User $user): void
    {
        $builder->where(function (Builder $builder) use ($user): Builder {
            return $builder->where('is_public', '=', true)
                ->orWhereHas('members', function (Builder $builder) use ($user): Builder {
                    return $builder->whereBelongsTo($user, 'user');
                });
        });
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isArchived(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => isset($attributes['archived_at']),
        );
    }
}
