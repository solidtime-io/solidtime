<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Service\Dto\ReportPropertiesDto;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string $organization_id
 * @property bool $is_public
 * @property Carbon|null $public_until
 * @property string|null $share_secret
 * @property ReportPropertiesDto $properties
 * @property-read Organization $organization
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static ReportFactory factory()
 */
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'bool',
        'public_until' => 'datetime',
        'properties' => ReportPropertiesDto::class,
    ];

    public function getShareableLink(): ?string
    {
        if ($this->is_public && $this->share_secret !== null) {
            return route('shared-report').'#'.$this->share_secret;
        }

        return null;
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}
