<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\Role;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Service\MemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class OrganizationInvitationController extends Controller
{
    public function accept(OrganizationInvitation $invitation, MemberService $memberService): RedirectResponse
    {
        $email = strtolower($invitation->email);
        $role = Role::tryFrom($invitation->role);
        if ($role === null || $role === Role::Owner || $role === Role::Placeholder) {
            throw new RuntimeException('Invalid role');
        }

        $organization = $invitation->organization;
        $invitee = User::query()
            ->where('email', $email)
            ->where('is_placeholder', '=', false)
            ->first();

        // No account yet — finish on registration.
        if ($invitee === null) {
            if ($invitation->accepted_at === null) {
                $invitation->accepted_at = now();
                $invitation->save();
            }

            return redirect(route('register'))
                ->with('bannerText', __('Please create an account to finish joining the :organization organization.', [
                    'organization' => $organization->name,
                ]))
                ->with('bannerStyle', 'info');
        }

        $alreadyMember = $memberService->isEmailAlreadyMember($organization, $email);
        if (! $alreadyMember) {
            $memberService->addMember($invitee, $organization, $role);
            $invitation->delete();
        }

        // Logged out — banner on /login.
        if (! Auth::check()) {
            return redirect(route('login'))
                ->with('bannerText', __('Great! You have accepted the invitation to join the :organization organization. Please log in to access it.', [
                    'organization' => $organization->name,
                ]))
                ->with('bannerStyle', 'success');
        }

        // Logged in — banner on /dashboard.
        if ($alreadyMember) {
            return redirect(route('dashboard'))
                ->with('bannerText', __('You are already a member of the :organization organization.', [
                    'organization' => $organization->name,
                ]))
                ->with('bannerStyle', 'danger');
        }

        return redirect(route('dashboard'))
            ->with('bannerText', __('Great! You have accepted the invitation to join the :organization organization.', [
                'organization' => $organization->name,
            ]))
            ->with('bannerStyle', 'success');
    }
}
