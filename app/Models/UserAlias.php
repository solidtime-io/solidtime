<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAlias extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'user_aliases';

    protected $fillable = [
        'id',
        'user_id',
        'alias',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
