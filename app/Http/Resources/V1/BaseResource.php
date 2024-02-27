<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    protected function formatDateTime(?Carbon $carbon): ?string
    {
        return $carbon?->toIso8601ZuluString();
    }
}
