<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CustomAuditable;
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
use Illuminate\Support\Facades\DB;
use Korridor\LaravelComputedAttributes\ComputedAttributes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $name
 * @property string $project_id
 * @property string $organization_id
 * @property Carbon|null $done_at
 * @property int|null $estimated_time
 * @property int $spent_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read Organization $organization
 * @property-read Collection<int, TimeEntry> $timeEntries
 * @property-read bool $is_done
 *
 * @method static TaskFactory factory()
 */
class Task extends Model implements AuditableContract
{
    use ComputedAttributes;
    use CustomAuditable;

    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'string',
        'estimated_time' => 'integer',
        'done_at' => 'datetime',
    ];

    /**
     * The attributes that are computed. (f.e. for performance reasons)
     * These attributes can be regenerated at any time.
     *
     * @var string[]
     */
    protected array $computed = [
        'spent_time',
    ];

    /**
     * Attributes to exclude from the Audit.
     *
     * @var array<string>
     */
    protected array $auditExclude = [
        'spent_time',
    ];

    public function getSpentTimeComputed(): ?int
    {
        if ($this->hasAttribute('spent_time_computed')) {
            return $this->attributes['spent_time_computed'] === null ? 0 : (int) $this->attributes['spent_time_computed'];
        } else {
            /** @var object{ spent_time: string } $result */
            $result = $this->timeEntries()
                ->whereNotNull('end')
                ->selectRaw('sum(extract(epoch from ("end" - start))) as spent_time')
                ->first();

            return (int) $result->spent_time;
        }
    }

    /**
     * This scope will be applied during the computed property generation with artisan computed-attributes:generate.
     *
     * @param  Builder<Task>  $builder
     * @param  array<string>  $attributes  Attributes that will be generated.
     * @return Builder<Task>
     */
    public function scopeComputedAttributesGenerate(Builder $builder, array $attributes): Builder
    {
        if (in_array('spent_time', $attributes, true)) {
            $builder->withAggregate('timeEntries as spent_time_computed', DB::raw('extract(epoch from ("end" - start))'), 'sum');
        }

        return $builder;
    }

    /**
     * This scope will be applied during the computed property validation with artisan computed-attributes:validate.
     *
     * @param  Builder<Task>  $builder
     * @param  array<string>  $attributes  Attributes that will be validated.
     * @return Builder<Task>
     */
    public function scopeComputedAttributesValidate(Builder $builder, array $attributes): Builder
    {
        return $this->scopeComputedAttributesGenerate($builder, $attributes);
    }

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
