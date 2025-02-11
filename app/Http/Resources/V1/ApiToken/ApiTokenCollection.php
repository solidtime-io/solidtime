<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\ApiToken;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiTokenCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ApiTokenResource::class;
}
