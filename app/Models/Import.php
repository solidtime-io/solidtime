<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImportSource;
use App\Enums\ImportStatus;
use App\Models\Concerns\HasUuids;
use Database\Factories\ImportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $type
 * @property ImportSource $source
 * @property ImportStatus $status
 * @property string $timezone
 * @property string|null $file_path
 * @property array<string, mixed>|null $credentials
 * @property array<string, mixed>|null $options
 * @property bool $ignore_limits
 * @property array<string, mixed>|null $report
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
 * @method static ImportFactory factory()
 */
class Import extends Model
{
    /** @use HasFactory<ImportFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * Time in seconds a running import can exceed the job timeout before it is considered stale.
     * Note: The job itself can not mark a timed out import as failed, since the timeout handler runs while the
     * import transaction is still open and the change is rolled back when the worker is killed.
     */
    private const int RUNNING_STALE_BUFFER_SECONDS = 300;

    /**
     * Time in seconds a pending import can exceed the job timeout before it is considered stale.
     */
    private const int PENDING_STALE_BUFFER_SECONDS = 3600;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'source' => ImportSource::class,
        'status' => ImportStatus::class,
        'credentials' => 'encrypted:array',
        'options' => 'array',
        'ignore_limits' => 'bool',
        'report' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'credentials',
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
     * The user that started the import, null if the user was deleted.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param  Builder<Import>  $builder
     * @return Builder<Import>
     */
    public function scopeActive(Builder $builder): Builder
    {
        return $builder->whereIn('status', ImportStatus::active());
    }

    /**
     * Active imports that are not finished in time, for example because the worker crashed or the job timed out.
     * The buffers cover small delays after the job timeout (running) and the time the job waits in the queue (pending).
     *
     * @param  Builder<Import>  $builder
     * @return Builder<Import>
     */
    public function scopeStale(Builder $builder): Builder
    {
        $jobTimeout = (int) config('import.job_timeout');
        $runningStaleBefore = Carbon::now()->subSeconds($jobTimeout + self::RUNNING_STALE_BUFFER_SECONDS);
        $pendingStaleBefore = Carbon::now()->subSeconds($jobTimeout + self::PENDING_STALE_BUFFER_SECONDS);

        return $builder->where(function (Builder $query) use ($runningStaleBefore, $pendingStaleBefore): void {
            $query->where(function (Builder $query) use ($runningStaleBefore): void {
                $query->where('status', ImportStatus::Running)
                    ->where('started_at', '<', $runningStaleBefore);
            })->orWhere(function (Builder $query) use ($pendingStaleBefore): void {
                $query->where('status', ImportStatus::Pending)
                    ->where('created_at', '<', $pendingStaleBefore);
            });
        });
    }
}
