<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

abstract class BaseResource extends JsonResource
{
    protected function formatDateTime(?Carbon $carbon): ?string
    {
        return $carbon?->toIso8601ZuluString();

    }
}
