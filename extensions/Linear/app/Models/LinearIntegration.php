<?php

declare(strict_types=1);

namespace Extensions\Linear\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $organization_id
 * @property string $access_token
 * @property string $refresh_token
 * @property Carbon $token_expires_at
 * @property string $linear_user_id
 * @property string|null $webhook_secret
 * @property string|null $webhook_id
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Organization $organization
 */
class LinearIntegration extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'organization_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'linear_user_id',
        'webhook_secret',
        'webhook_id',
        'last_synced_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at->isPast();
    }

    public function isTokenExpiringSoon(): bool
    {
        return $this->token_expires_at->isBefore(now()->addMinutes(5));
    }
}
