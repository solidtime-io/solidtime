<?php

declare(strict_types=1);

namespace App\Models\Passport;

use Database\Factories\Passport\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Passport\Client as PassportClient;

/**
 * @property string $id
 * @property string|null $user_id
 * @property string $name
 * @property string|null $secret
 * @property string|null $provider
 * @property string $redirect
 * @property bool $personal_access_client
 * @property bool $password_client
 * @property bool $revoked
 */
class Client extends PassportClient
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;
}
