<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Project;

use App\Http\Resources\PaginatedResourceCollection;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProjectCollection extends ResourceCollection implements PaginatedResourceCollection
{
    private bool $showBillableRates;

    public function __construct($resource, bool $showBillableRates)
    {
        parent::__construct($resource);
        $this->showBillableRates = $showBillableRates;
    }

    protected function collects(): ?string
    {
        return null;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<array<string, string|bool|int|null>>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(function (Project $project) use ($request): array {
            return (new ProjectResource($project, $this->showBillableRates))
                ->toArray($request);
        })->all();
    }
}
