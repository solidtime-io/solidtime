<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

class TaskResourceTest extends FilamentTestCase
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

    public function test_can_list_tasks(): void
    {
        // Arrange
        $tasks = Task::factory()->createMany(5);

        // Act
        $response = Livewire::test(TaskResource\Pages\ListTasks::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($tasks);
    }
}
