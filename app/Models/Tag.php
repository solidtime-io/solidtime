<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CustomAuditable;
use App\Models\Concerns\HasUuids;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;

/**
 * @property string $id
 * @property string $name
 * @property string $organization_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 *
 * @method static TagFactory factory()
 */
class Tag extends Model implements AuditableContract
{
    use CustomAuditable;

    /** @use HasFactory<TagFactory> */
    use HasFactory;

    use HasJsonRelationships;
    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'string',
    ];

    /**
     * @return BelongsTo<Organization, Tag>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * Warning: This relation based on a JSON column. Please make sure that there are no performance issues, before using it.
     *
     * @return HasManyJson<TimeEntry, $this>
     */
    public function timeEntries(): HasManyJson
    {
        return $this->hasManyJson(TimeEntry::class, 'tags');
    }
}
