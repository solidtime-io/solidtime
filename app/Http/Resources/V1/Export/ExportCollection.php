<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Export;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ExportCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ExportResource::class;
}
