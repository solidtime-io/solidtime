<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Member;

use Illuminate\Http\Resources\Json\ResourceCollection;

class MemberCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = MemberResource::class;
}
