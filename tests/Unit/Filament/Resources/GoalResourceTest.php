<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\GoalResource;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(GoalResource::class)]
class GoalResourceTest extends FilamentTestCase
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

    public function test_can_list_goals(): void
    {
        // Arrange
        $goals = Goal::factory()->createMany(5);

        // Act
        $response = Livewire::test(GoalResource\Pages\ListGoals::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($goals);
    }

    public function test_can_see_view_page_of_goal(): void
    {
        // Arrange
        $goal = Goal::factory()->create();

        // Act
        $response = Livewire::test(GoalResource\Pages\ViewGoal::class, ['record' => $goal->getKey()]);

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_see_edit_page_of_goal(): void
    {
        // Arrange
        $goal = Goal::factory()->create();

        // Act
        $response = Livewire::test(GoalResource\Pages\EditGoal::class, ['record' => $goal->getKey()]);

        // Assert
        $response->assertSuccessful();
    }
}
