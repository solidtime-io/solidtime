<?php

declare(strict_types=1);

namespace App\Models\Passport;

use App\Models\User;
use Database\Factories\Passport\TokenFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Passport\Token as PassportToken;

/**
 * @property string $id
 * @property null|string $user_id
 * @property string $client_id
 * @property null|string $name
 * @property array<string> $scopes
 * @property bool $revoked
 * @property Carbon|null $reminder_sent_at
 * @property Carbon|null $expired_info_sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $expires_at
 * @property-read Client|null $client
 * @property-read User|null $user
 *
 * @method Builder<Token> isApiToken(bool $isApiToken = true)
 */
class Token extends PassportToken
{
    /** @use HasFactory<TokenFactory> */
    use HasFactory;

    /**
     * Get the client that the token belongs to.
     *
     * @return BelongsTo<Client, $this>
     */
    // @phpstan-ignore method.childReturnType
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    /**
     * Get the user that the token belongs to.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @return BelongsTo<User, $this>
     */
    // @phpstan-ignore method.childReturnType
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'revoked' => 'bool',
            'expires_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'expired_info_sent_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeIsApiToken(Builder $query, bool $isApiToken = true): Builder
    {
        if ($isApiToken) {
            return $query->whereHas('client', function (Builder $query): void {
                /** @var Builder<Client> $query */
                $query->whereJsonContains('grant_types', 'personal_access');
            });
        } else {
            return $query->whereHas('client', function (Builder $query): void {
                /** @var Builder<Client> $query */
                $query->whereJsonDoesntContain('grant_types', 'personal_access');
            });
        }

    }
}
