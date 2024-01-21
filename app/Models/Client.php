<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $name
 * @property string $organization_id
 * @property-read Team $organization
 *
 * @method static ClientFactory factory()
 */
class Client extends Model
{
    use HasFactory;
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
     * @return BelongsTo<Team, Client>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'organization_id');
    }
}
