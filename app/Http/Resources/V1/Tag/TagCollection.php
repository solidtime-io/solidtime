<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Tag;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TagCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = TagResource::class;
}
