<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterval;
use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $description
 * @property Carbon $start
 * @property Carbon|null $end
 * @property bool $billable
 * @property array $tags
 * @property string $user_id
 * @property-read User $user
 * @property string $organization_id
 * @property-read Organization $organization
 * @property string|null $project_id
 * @property-read Project|null $project
 * @property string|null $task_id
 * @property-read Task|null $task
 *
 * @method static TimeEntryFactory factory()
 */
class TimeEntry extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'description' => 'string',
        'start' => 'datetime',
        'end' => 'datetime',
        'billable' => 'bool',
        'tags' => 'array',
    ];

    public function getDuration(): ?CarbonInterval
    {
        return $this->end === null ? null : $this->start->diffAsCarbonInterval($this->end);
    }

    /**
     * @return BelongsTo<User, TimeEntry>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Organization, TimeEntry>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return BelongsTo<Project, TimeEntry>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * @return BelongsTo<Task, TimeEntry>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
