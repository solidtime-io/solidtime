<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\VacationRequestStatus;
use App\Http\Requests\V1\VacationRequest\VacationRequestIndexRequest;
use App\Http\Requests\V1\VacationRequest\VacationRequestStoreRequest;
use App\Http\Requests\V1\VacationRequest\VacationRequestUpdateRequest;
use App\Http\Resources\V1\VacationRequest\VacationRequestCollection;
use App\Http\Resources\V1\VacationRequest\VacationRequestResource;
use App\Models\Member;
use App\Models\Organization;
use App\Models\VacationRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class VacationRequestController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?VacationRequest $vacationRequest = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($vacationRequest !== null && $vacationRequest->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Vacation request does not belong to organization');
        }
    }

    private function getCurrentMember(Organization $organization): Member
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $member = Member::query()
            ->where('organization_id', $organization->getKey())
            ->where('user_id', $user->getKey())
            ->firstOrFail();

        return $member;
    }

    /**
     * List vacation requests
     *
     * @return VacationRequestCollection<VacationRequestResource>
     *
     * @throws AuthorizationException
     */
    public function index(Organization $organization, VacationRequestIndexRequest $request): VacationRequestCollection
    {
        $canViewAll = $this->hasPermission($organization, 'vacation-requests:view:all');
        $currentMember = $this->getCurrentMember($organization);

        $query = VacationRequest::query()
            ->where('organization_id', $organization->getKey())
            ->with(['member.user', 'reviewer.user'])
            ->orderBy('start_date', 'desc');

        if (! $canViewAll) {
            $query->where('member_id', $currentMember->getKey());
        } elseif ($request->filled('member_id')) {
            $query->where('member_id', $request->input('member_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $vacationRequests = $query->paginate(config('app.pagination_per_page_default'));

        return VacationRequestCollection::make($vacationRequests);
    }

    /**
     * Create a vacation request
     *
     * @throws AuthorizationException
     */
    public function store(Organization $organization, VacationRequestStoreRequest $request): VacationRequestResource
    {
        $currentMember = $this->getCurrentMember($organization);
        $canManageAll = $this->hasPermission($organization, 'vacation-requests:manage:all');

        // Determine target member
        $memberId = $currentMember->getKey();
        if ($canManageAll && $request->filled('member_id')) {
            $memberId = $request->input('member_id');
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $halfDay = (bool) $request->input('half_day', false);

        $daysCount = $this->calculateWorkingDays($startDate, $endDate, $halfDay);

        $vacationRequest = VacationRequest::create([
            'organization_id' => $organization->getKey(),
            'member_id' => $memberId,
            'type' => $request->input('type'),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'half_day' => $halfDay,
            'days_count' => $daysCount,
            'status' => VacationRequestStatus::Pending->value,
            'private_note' => $request->input('private_note'),
            'public_note' => $request->input('public_note'),
        ]);

        $vacationRequest->load(['member.user', 'reviewer.user']);

        return VacationRequestResource::make($vacationRequest);
    }

    /**
     * Update vacation request status (approve/reject/withdraw)
     *
     * @throws AuthorizationException
     */
    public function update(Organization $organization, VacationRequest $vacationRequest, VacationRequestUpdateRequest $request): VacationRequestResource
    {
        $this->checkPermission($organization, 'vacation-requests:view:all', $vacationRequest);

        $currentMember = $this->getCurrentMember($organization);
        $newStatus = VacationRequestStatus::from($request->input('status'));

        $canManageAll = $this->hasPermission($organization, 'vacation-requests:manage:all');

        // Only admin/manager can approve or reject
        if (in_array($newStatus, [VacationRequestStatus::Approved, VacationRequestStatus::Rejected], true) && ! $canManageAll) {
            throw new AuthorizationException('Only admins can approve or reject vacation requests');
        }

        // Only the requester can withdraw their own request
        if ($newStatus === VacationRequestStatus::Withdrawn && $vacationRequest->member_id !== $currentMember->getKey() && ! $canManageAll) {
            throw new AuthorizationException('You can only withdraw your own vacation requests');
        }

        $updateData = ['status' => $newStatus->value];

        if (in_array($newStatus, [VacationRequestStatus::Approved, VacationRequestStatus::Rejected], true)) {
            $updateData['reviewed_by'] = $currentMember->getKey();
            $updateData['reviewed_at'] = now()->toDateTimeString();
        }

        $vacationRequest->update($updateData);
        $vacationRequest->load(['member.user', 'reviewer.user']);

        return VacationRequestResource::make($vacationRequest);
    }

    /**
     * Delete a vacation request
     *
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, VacationRequest $vacationRequest): JsonResponse
    {
        $currentMember = $this->getCurrentMember($organization);
        $canManageAll = $this->hasPermission($organization, 'vacation-requests:manage:all');

        if ($vacationRequest->organization_id !== $organization->getKey()) {
            throw new AuthorizationException;
        }

        if (! $canManageAll && $vacationRequest->member_id !== $currentMember->getKey()) {
            throw new AuthorizationException;
        }

        $vacationRequest->delete();

        return response()->json(null, 204);
    }

    private function calculateWorkingDays(Carbon $start, Carbon $end, bool $halfDay): int
    {
        if ($halfDay) {
            return 1;
        }

        $days = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $days++;
            }
            $current->addDay();
        }

        return max(1, $days);
    }
}
