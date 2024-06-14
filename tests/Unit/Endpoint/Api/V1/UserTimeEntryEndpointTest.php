<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\UserTimeEntryController;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;
use TiMacDonald\Log\LogEntry;

#[UsesClass(UserTimeEntryController::class)]
class UserTimeEntryEndpointTest extends ApiEndpointTestAbstract
{
    public function test_my_active_endpoint_returns_unauthorized_if_user_is_not_logged_in(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);

        // Act
        $response = $this->getJson(route('api.v1.users.time-entries.my-active'));

        // Assert
        $response->assertUnauthorized();
    }

    public function test_my_active_endpoint_returns_current_time_entry_of_logged_in_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $activeTimeEntry = TimeEntry::factory()->forMember($data->member)->active()->create();
        $inactiveTimeEntry = TimeEntry::factory()->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.users.time-entries.my-active'));

        // Assert
        $response->assertSuccessful();
        $response->assertJsonPath('data.id', $activeTimeEntry->getKey());
    }

    public function test_my_active_endpoint_logs_a_warning_if_user_has_multiple_active_time_entries_and_return_the_latest_one(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $activeTimeEntry1 = TimeEntry::factory()->forMember($data->member)->active()->start(Carbon::now()->subDay())->create();
        $activeTimeEntry2 = TimeEntry::factory()->forMember($data->member)->active()->start(Carbon::now())->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.users.time-entries.my-active'));

        // Assert
        Log::assertLogged(fn (LogEntry $log) => $log->level === 'warning'
            && $log->message === 'User has more than one active time entry.'
            && $log->context === ['user' => $data->user->getKey()]
        );
        $response->assertSuccessful();
        $response->assertJsonPath('data.id', $activeTimeEntry2->getKey());
    }

    public function test_my_active_endpoint_returns_not_found_if_user_has_no_active_time_entry(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $inactiveTimeEntry = TimeEntry::factory()->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.users.time-entries.my-active'));

        // Assert
        $response->assertNotFound();
    }
}
