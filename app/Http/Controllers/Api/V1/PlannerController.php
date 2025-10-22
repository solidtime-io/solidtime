<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlannerController extends Controller
{
    public function index(Organization $organization, Request $request): JsonResponse
    {
        abort_unless((bool) config('planner.enabled'), 404);
        // Minimal stub: will list phases/milestones later. Keep diff small.
        return response()->json([
            'enabled' => true,
            'organization_id' => $organization->getKey(),
        ]);
    }
}
