<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Client;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ClientCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ClientResource::class;
}
