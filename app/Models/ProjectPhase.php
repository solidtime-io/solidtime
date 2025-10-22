<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectPhase extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'project_phases';

    protected $fillable = [
        'id',
        'project_id',
        'phase_template_id',
        'name',
        'position',
        'status',
        'started_at',
        'completed_at',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PhaseTemplate::class, 'phase_template_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(PhaseMilestone::class, 'project_phase_id');
    }
}
