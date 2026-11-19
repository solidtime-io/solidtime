<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Export;
use App\Models\Import;
use App\Models\Member;
use App\Models\Organization;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Organization::class)]
class OrganizationModelTest extends ModelTestAbstract
{
    public function test_it_has_many_members(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $members = Member::factory()->forOrganization($organization)->createMany(3);

        // Act
        $organization->refresh();
        $membersRel = $organization->members;

        // Assert
        $this->assertNotNull($membersRel);
        $this->assertCount(3, $membersRel);
    }

    public function test_it_has_many_imports(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        Import::factory()->forOrganization($organization)->completed()->createMany(2);
        Import::factory()->forOrganization($otherOrganization)->completed()->create();

        // Act
        $organization->refresh();
        $imports = $organization->imports;

        // Assert
        $this->assertCount(2, $imports);
        $this->assertTrue($imports->every(fn (Import $import): bool => $import->organization_id === $organization->getKey()));
    }

    public function test_it_has_many_exports(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        Export::factory()->forOrganization($organization)->completed()->createMany(2);
        Export::factory()->forOrganization($otherOrganization)->completed()->create();

        // Act
        $organization->refresh();
        $exports = $organization->exports;

        // Assert
        $this->assertCount(2, $exports);
        $this->assertTrue($exports->every(fn (Export $export): bool => $export->organization_id === $organization->getKey()));
    }
}
