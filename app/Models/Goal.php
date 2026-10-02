<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GoalComparison;
use App\Enums\GoalPeriod;
use App\Enums\GoalType;
use App\Enums\Weekday;
use App\Models\Concerns\HasUuids;
use App\Service\Dto\GoalFiltersDto;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property GoalType $type
 * @property GoalComparison $comparison
 * @property int $target_seconds
 * @property GoalPeriod $period
 * @property GoalFiltersDto $filters
 * @property string $timezone
 * @property Weekday $week_start
 * @property Carbon|null $archived_at
 * @property-read bool $is_archived
 * @property string $organization_id
 * @property string|null $member_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read Member|null $member
 *
 * @method static GoalFactory factory()
 */
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => GoalType::class,
        'comparison' => GoalComparison::class,
        'target_seconds' => 'int',
        'period' => GoalPeriod::class,
        'filters' => GoalFiltersDto::class,
        'week_start' => Weekday::class,
        'archived_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * The member whose time entries count towards the goal, null for goals that count every member.
     *
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function isPersonalGoalOf(Member $member): bool
    {
        return $this->type === GoalType::Personal && $this->member_id === $member->getKey();
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

    /**
     * @param  Builder<Goal>  $builder
     * @return Builder<Goal>
     */
    public function scopeArchived(Builder $builder): Builder
    {
        return $builder->whereNotNull('archived_at');
    }

    /**
     * @param  Builder<Goal>  $builder
     * @return Builder<Goal>
     */
    public function scopeNotArchived(Builder $builder): Builder
    {
        return $builder->whereNull('archived_at');
    }
}
