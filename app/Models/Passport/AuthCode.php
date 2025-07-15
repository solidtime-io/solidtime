<?php

declare(strict_types=1);

namespace App\Models\Passport;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Passport\AuthCode as PassportAuthCode;

/**
 * @property string $id
 * @property string $user_id
 * @property string $client_id
 * @property string|null $scopes
 * @property bool $revoked
 * @property Carbon $expires_at
 */
class AuthCode extends PassportAuthCode
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
