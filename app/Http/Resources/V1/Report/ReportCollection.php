<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Report;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ReportCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ReportResource::class;
}
