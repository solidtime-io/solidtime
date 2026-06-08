<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CurrencyFormat;
use App\Enums\DateFormat;
use App\Enums\IntervalFormat;
use App\Enums\NumberFormat;
use App\Enums\TimeFormat;
use App\Models\Concerns\CustomAuditable;
use App\Models\Concerns\HasUuids;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $name
 * @property bool $personal_team
 * @property string $currency
 * @property int|null $billable_rate
 * @property string $user_id
 * @property bool $employees_can_see_billable_rates
 * @property bool $employees_can_manage_tasks
 * @property bool $prevent_overlapping_time_entries
 * @property User $owner
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, User> $users
 * @property Collection<int, User> $realUsers
 * @property-read Collection<int, OrganizationInvitation> $organizationInvitations
 * @property Member $membership
 * @property NumberFormat $number_format
 * @property CurrencyFormat $currency_format
 * @property DateFormat $date_format
 * @property IntervalFormat $interval_format
 * @property TimeFormat $time_format
 *
 * @method static OrganizationFactory factory()
 */
class Organization extends Model implements AuditableContract
{
    use CustomAuditable;

    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'string',
        'personal_team' => 'boolean',
        'currency' => 'string',
        'employees_can_see_billable_rates' => 'boolean',
        'employees_can_manage_tasks' => 'boolean',
        'prevent_overlapping_time_entries' => 'boolean',
        'number_format' => NumberFormat::class,
        'currency_format' => CurrencyFormat::class,
        'date_format' => DateFormat::class,
        'interval_format' => IntervalFormat::class,
        'time_format' => TimeFormat::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'personal_team',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
    ];

    /**
     * Get all the users that belong to the team.
     *
     * @return BelongsToMany<User, $this, Pivot, 'membership'>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, Member::class)
            ->withPivot([
                'id',
                'role',
                'billable_rate',
            ])
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * Get the owner of the team.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Member, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * @return BelongsToMany<User, $this, Pivot, 'membership'>
     */
    public function realUsers(): BelongsToMany
    {
        return $this->users()
            ->where('is_placeholder', false);
    }

    /**
     * @return HasMany<OrganizationInvitation, $this>
     */
    public function organizationInvitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class, 'organization_id');
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  array<int, string>  $columns
     *
     * @throws ModelNotFoundException<Model>
     */
    public static function findOrFail(string $id, array $columns = ['*']): Model
    {
        if (! Str::isUuid($id)) {
            throw (new ModelNotFoundException)->setModel(
                self::class, $id
            );
        }

        return parent::findOrFail($id, $columns);
    }
}
