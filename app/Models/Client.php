<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CustomAuditable;
use App\Models\Concerns\HasUuids;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $name
 * @property string $organization_id
 * @property-read bool $is_archived
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 *
 * @method static ClientFactory factory()
 */
class Client extends Model implements AuditableContract
{
    use CustomAuditable;

    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'string',
        'archived_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_id');
    }

    /**
     * @param  Builder<Client>  $builder
     * @return Builder<Client>
     */
    public function scopeVisibleByEmployee(Builder $builder, User $user): Builder
    {
        return $builder->whereHas('projects', function (Builder $builder) use ($user): Builder {
            /** @var Builder<Project> $builder */
            return $builder->visibleByEmployee($user);
        });
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isArchived(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => isset($attributes['archived_at']),
        );
    }
}
