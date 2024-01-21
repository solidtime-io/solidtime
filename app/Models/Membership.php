<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Jetstream\Membership as JetstreamMembership;

/**
 * @property string $id
 * @property string $role
 * @property string $organization_id
 * @property string $user_id
 * @property string $created_at
 * @property string $updated_at
 * @property-read Organization $organization
 * @property-read User $user
 */
class Membership extends JetstreamMembership
{
    use HasUuids;

    /**
     * The table associated with the pivot model.
     *
     * @var string
     */
    protected $table = 'organization_user';
}
