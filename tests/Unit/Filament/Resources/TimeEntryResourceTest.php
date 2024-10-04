<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\TimeEntryResource;
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
}
