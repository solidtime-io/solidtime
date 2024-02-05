<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

class ProjectResourceTest extends FilamentTestCase
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

    public function test_can_list_projects(): void
    {
        // Arrange
        $projects = Project::factory()->createMany(5);

        // Act
        $response = Livewire::test(ProjectResource\Pages\ListProjects::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($projects);
    }
}
