<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\TimeEntryResource;
use App\Models\Member;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(TimeEntryResource::class)]
class TimeEntryResourceTest extends FilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('auth.super_admins', ['admin@example.com']);
        $user = User::factory()->withPersonalOrganization()->create([
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($user);
    }

    public function test_can_list_time_entry(): void
    {
        // Arrange
        $timeEntry = TimeEntry::factory()->createMany(5);

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\ListTimeEntries::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($timeEntry);
    }

    public function test_can_see_edit_page_of_time_entry(): void
    {
        // Arrange
        $timeEntry = TimeEntry::factory()->create();

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\EditTimeEntry::class, ['record' => $timeEntry->getKey()]);

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_see_create_page_of_time_entry(): void
    {
        // Act
        $response = Livewire::test(TimeEntryResource\Pages\CreateTimeEntry::class);

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_create_time_entry(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()
            ->forOrganization($organization)
            ->forUser($user)
            ->create();

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\CreateTimeEntry::class)
            ->fillForm([
                'description' => 'Test time entry',
                'billable' => true,
                'start' => '2024-01-01 08:00:00',
                'end' => '2024-01-01 10:00:00',
                'member_id' => $member->getKey(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Assert
        $response->assertSuccessful();
        $timeEntry = TimeEntry::where('description', 'Test time entry')->first();
        $this->assertNotNull($timeEntry);
        $this->assertSame($member->getKey(), $timeEntry->member_id);
        $this->assertSame($user->getKey(), $timeEntry->user_id);
        $this->assertSame($organization->getKey(), $timeEntry->organization_id);
        $this->assertTrue($timeEntry->billable);
    }

    public function test_can_create_time_entry_and_derives_user_and_organization_from_member(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()
            ->forOrganization($organization)
            ->forUser($user)
            ->create();
        $otherUser = User::factory()->create();
        $otherOrganization = Organization::factory()->create();

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\CreateTimeEntry::class)
            ->fillForm([
                'description' => 'Derived fields test',
                'billable' => false,
                'start' => '2024-03-01 09:00:00',
                'end' => '2024-03-01 11:00:00',
                'member_id' => $member->getKey(),
                'user_id' => $otherUser->getKey(),
                'organization_id' => $otherOrganization->getKey(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Assert
        $response->assertSuccessful();
        $timeEntry = TimeEntry::where('description', 'Derived fields test')->first();
        $this->assertNotNull($timeEntry);
        $this->assertSame($user->getKey(), $timeEntry->user_id);
        $this->assertSame($organization->getKey(), $timeEntry->organization_id);
    }

    public function test_can_update_time_entry(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()
            ->forOrganization($organization)
            ->forUser($user)
            ->create();
        $timeEntry = TimeEntry::factory()->forMember($member)->create();

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\EditTimeEntry::class, ['record' => $timeEntry->getKey()])
            ->fillForm([
                'description' => 'Updated description',
                'billable' => true,
                'start' => '2024-02-01 08:00:00',
                'end' => '2024-02-01 12:00:00',
                'member_id' => $member->getKey(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Assert
        $response->assertSuccessful();
        $timeEntry->refresh();
        $this->assertSame('Updated description', $timeEntry->description);
        $this->assertTrue($timeEntry->billable);
        $this->assertSame($user->getKey(), $timeEntry->user_id);
        $this->assertSame($organization->getKey(), $timeEntry->organization_id);
    }

    public function test_update_time_entry_derives_user_and_organization_from_new_member(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()
            ->forOrganization($organization)
            ->forUser($user)
            ->create();
        $timeEntry = TimeEntry::factory()->create();

        $newOrganization = Organization::factory()->create();
        $newUser = User::factory()->create();
        $newMember = Member::factory()
            ->forOrganization($newOrganization)
            ->forUser($newUser)
            ->create();

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\EditTimeEntry::class, ['record' => $timeEntry->getKey()])
            ->fillForm([
                'description' => 'Reassigned entry',
                'billable' => false,
                'start' => '2024-02-01 08:00:00',
                'end' => '2024-02-01 12:00:00',
                'member_id' => $newMember->getKey(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Assert
        $response->assertSuccessful();
        $timeEntry->refresh();
        $this->assertSame($newMember->getKey(), $timeEntry->member_id);
        $this->assertSame($newUser->getKey(), $timeEntry->user_id);
        $this->assertSame($newOrganization->getKey(), $timeEntry->organization_id);
    }
}
