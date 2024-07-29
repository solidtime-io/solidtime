<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Events\AfterCreateOrganization;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizations_can_be_created(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);
        Event::fake([
            AfterCreateOrganization::class,
        ]);

        // Act
        $response = $this->post('/teams', [
            'name' => 'Test Organization',
        ]);

        // Assert
        $response->assertStatus(302);
        /** @var Organization|null $newOrganization */
        $ownedTeams = $user->fresh()->ownedTeams;
        $this->assertCount(2, $ownedTeams);
        $this->assertTrue($ownedTeams->contains('name', 'Test Organization'));
        $newOrganization = $ownedTeams->firstWhere('name', 'Test Organization');
        /** @var Member $member */
        $member = Member::query()->whereBelongsTo($user, 'user')->whereBelongsTo($newOrganization, 'organization')->firstOrFail();
        $this->assertSame(Role::Owner->value, $member->role);
        Event::assertDispatched(AfterCreateOrganization::class, function (AfterCreateOrganization $event) use ($newOrganization): bool {
            return $event->organization->is($newOrganization);
        });
    }
}
