<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilestoneTemplate extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'milestone_templates';

    protected $fillable = [
        'id',
        'phase_template_id',
        'name',
        'is_milestone',
        'due_offset_days',
        'position',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(PhaseTemplate::class, 'phase_template_id');
    }
}
