<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Ramsey\Uuid\Uuid;

trait HasUuids
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    /**
     * Generate a new UUID for the model.
     */
    public function newUniqueId(): string
    {
        return (string) Uuid::uuid4();
    }
}
