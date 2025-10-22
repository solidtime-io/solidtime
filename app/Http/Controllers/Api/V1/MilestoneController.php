<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function update(Organization $organization, string $milestoneId, Request $request): JsonResponse
    {
        abort_unless((bool) config('planner.enabled'), 404);
        // TODO: implement CRUD. For now, acknowledge.
        return response()->json(['ok' => true, 'milestone_id' => $milestoneId]);
    }
}
