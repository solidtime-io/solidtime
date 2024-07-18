<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $uuid
 * @property string $connection
 * @property string $queue
 * @property Carbon $failed_at
 */
class FailedJob extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'failed_at' => 'datetime',
        'payload' => 'json',
    ];
}
