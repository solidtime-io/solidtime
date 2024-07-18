<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\User;

use App\Enums\Weekday;
use App\Http\Resources\V1\BaseResource;
use App\Models\User;
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
            /** @var string $id ID of user */
            'id' => $this->resource->id,
            /** @var string $name Name of user */
            'name' => $this->resource->name,
            /** @var string $email Email of user */
            'email' => $this->resource->email,
            /** @var string $profile_photo_url Profile photo URL */
            'profile_photo_url' => $this->resource->profile_photo_url,
            /** @var string $timezone Timezone (f.e. Europe/Berlin or America/New_York) */
            'timezone' => $this->resource->timezone,
            /** @var Weekday $week_start Starting day of the week */
            'week_start' => $this->resource->week_start->value,
        ];
    }
}
