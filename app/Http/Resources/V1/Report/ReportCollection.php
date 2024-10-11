<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Report;

use App\Http\Resources\PaginatedResourceCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ReportCollection extends ResourceCollection implements PaginatedResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ReportResource::class;
}
