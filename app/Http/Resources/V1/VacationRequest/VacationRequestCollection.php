<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\VacationRequest;

use App\Http\Resources\PaginatedResourceCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class VacationRequestCollection extends ResourceCollection implements PaginatedResourceCollection
{
    public $collects = VacationRequestResource::class;
}
