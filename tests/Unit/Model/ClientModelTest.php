<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Client::class)]
class ClientModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_a_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $client = Client::factory()->forOrganization($organization)->create();

        // Act
        $client->refresh();
        $organizationRel = $client->organization;

        // Assert
        $this->assertNotNull($organizationRel);
        $this->assertTrue($organizationRel->is($organization));
    }

    public function test_it_has_many_projects(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $projects = Project::factory()->forClient($client)->createMany(4);
        $projectsOtherClient = Project::factory()->forClient($otherClient)->createMany(4);

        // Act
        $client->refresh();
        $projectsRel = $client->projects;

        // Assert
        $this->assertNotNull($projectsRel);
        $this->assertCount(4, $projectsRel);
        $this->assertTrue($projectsRel->first()->is($projects->first()));
    }

    public function test_accessor_is_archived_is_true_if_archived_at_is_not_null(): void
    {
        // Arrange
        $client = Client::factory()->archived()->create();

        // Act
        $client->refresh();
        $isArchived = $client->is_archived;

        // Assert
        $this->assertTrue($isArchived);
    }

    public function test_accessor_is_archived_is_false_if_archived_at_is_null(): void
    {
        // Arrange
        $client = Client::factory()->create();

        // Act
        $client->refresh();
        $isArchived = $client->is_archived;

        // Assert
        $this->assertFalse($isArchived);
    }
}
