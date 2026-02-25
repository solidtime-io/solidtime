<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\Role;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Service\MemberService;
use Illuminate\Http\RedirectResponse;
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

        $newOrganizationMember = User::query()
            ->where('email', $email)
            ->where('is_placeholder', '=', false)
            ->first();

        if ($newOrganizationMember === null) {
            if ($invitation->accepted_at === null) {
                $invitation->accepted_at = now();
                $invitation->save();
            }

            return redirect(route('register', [
                'bannerStyle' => 'info',
                'bannerText' => __('Please create an account to finish joining the :organization organization.', [
                    'organization' => $invitation->organization->name,
                ]),
            ]));
        } else {
            $organization = $invitation->organization;
            if ($memberService->isEmailAlreadyMember($organization, $email)) {
                return redirect(route('dashboard', [
                    'bannerStyle' => 'danger',
                    'bannerText' => __('You are already a member of the :organization organization.', [
                        'organization' => $organization->name,
                    ]),
                ]));
            }

            $memberService->addMember($newOrganizationMember, $organization, $role);

            $invitation->delete();

            return redirect(route('dashboard', [
                'bannerStyle' => 'success',
                'bannerText' => __('Great! You have accepted the invitation to join the :organization organization.', [
                    'organization' => $invitation->organization->name,
                ]),
            ]));
        }
    }
}
