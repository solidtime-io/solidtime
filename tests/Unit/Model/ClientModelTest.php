<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Client;
use App\Models\Team;

class ClientModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_a_organization(): void
    {
        // Arrange
        $organization = Team::factory()->create();
        $client = Client::factory()->forOrganization($organization)->create();

        // Act
        $client->refresh();
        $organizationRel = $client->organization;

        // Assert
        $this->assertNotNull($organizationRel);
        $this->assertTrue($organizationRel->is($organization));
    }
}
