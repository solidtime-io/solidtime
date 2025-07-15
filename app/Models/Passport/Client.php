<?php

declare(strict_types=1);

namespace App\Models\Passport;

use Database\Factories\Passport\ClientFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Laravel\Passport\Client as PassportClient;

/**
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $owner_type
 * @property string $name
 * @property string|null $secret
 * @property string|null $provider
 * @property array<string> $grant_types
 * @property array<string> $redirect_uris
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool $revoked
 */
class Client extends PassportClient
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     *
     * @return ClientFactory
     */
    protected static function newFactory(): Factory
    {
        return ClientFactory::new();
    }
}
