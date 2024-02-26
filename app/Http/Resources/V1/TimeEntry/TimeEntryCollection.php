<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\TimeEntry;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TimeEntryCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = TimeEntryResource::class;
}
