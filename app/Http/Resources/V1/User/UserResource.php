<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\User;

use App\Enums\Weekday;
use App\Http\Resources\V1\BaseResource;
use App\Models\User;
use App\Service\AuthenticationContract;
use App\Service\IdentityConfirmationService;
use Illuminate\Http\Request;

/**
 * @property User $resource
 */
class UserResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null|array<string>>
     */
    public function toArray(Request $request): array
    {
        return [
            /** ID of user */
            'id' => $this->resource->id,
            /** Name of user */
            'name' => $this->resource->name,
            /** Email of user */
            'email' => $this->resource->email,
            /** Email address awaiting verification (set when the user has requested an email change but not yet verified the new address) */
            'pending_email' => $this->resource->pending_email,
            /** Profile photo URL */
            'profile_photo_url' => $this->resource->profile_photo_url,
            /** Timezone (f.e. Europe/Berlin or America/New_York) */
            'timezone' => $this->resource->timezone,
            /** @var Weekday $week_start Starting day of the week */
            'week_start' => $this->resource->week_start->value,
            /** Whether to email the user when a time entry has been running for more than 8 hours */
            'send_time_entry_still_running_email' => $this->resource->send_time_entry_still_running_email,
            /** Whether the user confirms sensitive actions (like deleting their account) with their password. If not, an administrator has to do these actions for them. */
            'confirms_with_password' => app(IdentityConfirmationService::class)->confirmsWithPassword($this->resource),
            /** Whether the user may change their email address. If not, it is managed elsewhere (e.g. by the organization's identity provider). */
            'can_change_email' => app(AuthenticationContract::class)->canChangeEmail($this->resource),
        ];
    }
}
