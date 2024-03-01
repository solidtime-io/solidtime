<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;

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
}
