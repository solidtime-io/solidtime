<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectPhaseTemplate extends Model
{
    use HasFactory;
    use HasUuids;

    protected $casts = [
        'name' => 'string',
        'position' => 'integer',
    ];

    /** @return HasMany<ProjectMilestoneTemplate, $this> */
    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestoneTemplate::class, 'project_phase_template_id');
    }
}
