<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhaseTemplate extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'phase_templates';

    protected $fillable = [
        'id',
        'name',
        'position',
    ];

    public function milestones(): HasMany
    {
        return $this->hasMany(MilestoneTemplate::class, 'phase_template_id');
    }
}
