<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestoneTemplate extends Model
{
    use HasFactory;
    use HasUuids;

    protected $casts = [
        'name' => 'string',
        'is_milestone' => 'boolean',
        'due_offset_days' => 'integer',
        'position' => 'integer',
    ];

    /** @return BelongsTo<ProjectPhaseTemplate, $this> */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhaseTemplate::class, 'project_phase_template_id');
    }
}
