<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VacationRequestStatus;
use App\Enums\VacationRequestType;
use App\Models\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $member_id
 * @property VacationRequestType $type
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property bool $half_day
 * @property int $days_count
 * @property VacationRequestStatus $status
 * @property string|null $private_note
 * @property string|null $public_note
 * @property string|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read Member $member
 * @property-read Member|null $reviewer
 */
class VacationRequest extends Model
{
    use HasUuids;

    protected $table = 'vacation_requests';

    protected $fillable = [
        'organization_id',
        'member_id',
        'type',
        'start_date',
        'end_date',
        'half_day',
        'days_count',
        'status',
        'private_note',
        'public_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'type' => VacationRequestType::class,
        'status' => VacationRequestStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'half_day' => 'boolean',
        'days_count' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'reviewed_by');
    }
}
