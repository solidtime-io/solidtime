<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $organization_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
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
     * @return BelongsTo<Organization, Client>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return HasMany<Project>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_id');
    }
}
