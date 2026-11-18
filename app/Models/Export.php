<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExportStatus;
use App\Enums\ExportType;
use App\Models\Concerns\HasUuids;
use Database\Factories\ExportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property ExportType $type
 * @property ExportStatus $status
 * @property string|null $file_path
 * @property string|null $error_message
 * @property string|null $error_details
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property string $organization_id
 * @property string|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read User|null $user
 *
 * @method static ExportFactory factory()
 */
class Export extends Model
{
    /** @use HasFactory<ExportFactory> */
    use HasFactory;

    use HasUuids;

    private const int DOWNLOAD_FILENAME_SLUG_MAX_LENGTH = 40;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => ExportType::class,
        'status' => ExportStatus::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'error_details',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * The user that started the export, null if the user was deleted.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Filename that is used when the export file is downloaded, for example
     * "solidtime-export_acme-gmbh_2026-11-18_10-20_1f2470b2.zip".
     * The short export ID makes the filename unique, even for exports of organizations with the same name.
     *
     * @param  string  $timezone  Timezone of the date in the filename
     */
    public function getDownloadFilename(string $timezone): string
    {
        $organizationSlug = Str::limit(Str::slug($this->organization->name), self::DOWNLOAD_FILENAME_SLUG_MAX_LENGTH, '');
        $organizationSlug = trim($organizationSlug, '-');
        if ($organizationSlug === '') {
            $organizationSlug = 'organization';
        }
        $createdAt = ($this->created_at ?? Carbon::now())->copy()->setTimezone($timezone);

        return 'solidtime-export_'.$organizationSlug.'_'.$createdAt->format('Y-m-d_H-i').'_'.Str::substr($this->getKey(), 0, 8).'.zip';
    }

    /**
     * @param  Builder<Export>  $builder
     * @return Builder<Export>
     */
    public function scopeActive(Builder $builder): Builder
    {
        return $builder->whereIn('status', ExportStatus::active());
    }
}
