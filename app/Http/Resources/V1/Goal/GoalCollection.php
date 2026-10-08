<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Goal;

use App\Models\Goal;
use App\Service\Dto\GoalProgressDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GoalCollection extends ResourceCollection
{
    /**
     * @var array<string, GoalProgressDto>
     */
    private array $progressByGoalId;

    /**
     * @param  array<string, GoalProgressDto>  $progressByGoalId
     */
    public function __construct($resource, array $progressByGoalId)
    {
        parent::__construct($resource);
        $this->progressByGoalId = $progressByGoalId;
    }

    protected function collects(): ?string
    {
        return null;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<array<string, string|bool|int|null|array<string, string|bool|int|null|array<int, string>>>>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(function (Goal $goal) use ($request): array {
            return (new GoalResource($goal, $this->progressByGoalId[$goal->getKey()]))
                ->toArray($request);
        })->all();
    }
}
