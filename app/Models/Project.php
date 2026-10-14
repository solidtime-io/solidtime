<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CustomAuditable;
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
use Illuminate\Support\Facades\DB;
use Korridor\LaravelComputedAttributes\ComputedAttributes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
 * @property int|null $estimated_time
 * @property int $spent_time
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
class Project extends Model implements AuditableContract
{
    use ComputedAttributes;
    use CustomAuditable;

    /** @use HasFactory<ProjectFactory> */
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
        'archived_at' => 'datetime',
        'estimated_time' => 'integer',
        'spent_time' => 'integer',
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
     * @param  Builder<Project>  $builder
     * @param  array<string>  $attributes  Attributes that will be generated.
     * @return Builder<Project>
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
     * @param  Builder<Project>  $builder
     * @param  array<string>  $attributes  Attributes that will be validated.
     * @return Builder<Project>
     */
    public function scopeComputedAttributesValidate(Builder $builder, array $attributes): Builder
    {
        return $this->scopeComputedAttributesGenerate($builder, $attributes);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * @return HasMany<ProjectMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'project_id');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<TimeEntry, $this>
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
