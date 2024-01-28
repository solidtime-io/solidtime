<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Resources\TimeEntryResource;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

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
}
