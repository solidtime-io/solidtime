<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Invitation;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InvitationCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = InvitationResource::class;
}
