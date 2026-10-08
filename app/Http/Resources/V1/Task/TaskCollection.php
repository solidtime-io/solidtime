<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Task;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TaskCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = TaskResource::class;
}
