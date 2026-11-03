<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Import;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ImportCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ImportResource::class;
}
