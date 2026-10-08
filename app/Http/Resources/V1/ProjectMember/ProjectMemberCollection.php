<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\ProjectMember;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProjectMemberCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ProjectMemberResource::class;
}
