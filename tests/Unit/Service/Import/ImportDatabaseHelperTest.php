<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Service\Import\ImportDatabaseHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

#[CoversClass(ImportDatabaseHelper::class)]
#[UsesClass(ImportDatabaseHelper::class)]
class ImportDatabaseHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_key_attach_to_existing_returns_key_for_identifier_without_creating_model(): void
    {
        // Arrange
        $user = User::factory()->create();
        $helper = new ImportDatabaseHelper(User::class, ['email'], true);

        // Act
        $key = $helper->getKey([
            'email' => $user->email,
        ], [
            'name' => 'Test',
        ]);

        // Assert
        $this->assertSame($user->getKey(), $key);
    }

    public function test_get_key_attach_to_existing_creates_model_if_not_existing(): void
    {
        // Arrange
        $helper = new ImportDatabaseHelper(User::class, ['email'], true);

        // Act
        $key = $helper->getKey([
            'email' => 'test@mail.test',
        ], [
            'name' => 'Test',
            'timezone' => 'UTC',
        ]);

        // Assert
        $this->assertNotNull($key);
        $this->assertDatabaseHas(User::class, [
            'email' => 'test@mail.test',
            'name' => 'Test',
        ]);
    }

    public function test_get_key_not_attach_to_existing_is_not_implemented_yet(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $helper = new ImportDatabaseHelper(Project::class, ['name', 'organization_id'], false);

        // Act
        try {
            $key = $helper->getKey([
                'name' => $project->name,
                'organization_id' => $project->organization_id,
            ], [
                'color' => '#000000',
            ]);
        } catch (\Exception $e) {
            $this->assertSame('Not implemented', $e->getMessage());

            return;
        }

        // Assert
        $this->fail();
    }

    public function test_get_key_by_external_identifier_returns_key_for_external_identifier(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();
        $externalIdentifier1 = '12345';
        $externalIdentifier2 = '54321';
        $helper = new ImportDatabaseHelper(Project::class, ['name', 'organization_id'], true);
        $helper->getKey([
            'name' => $project->name,
            'organization_id' => $organization->getKey(),
        ], [
            'color' => '#000000',
        ], $externalIdentifier1);
        $helper->getKey([
            'name' => 'Not existing project',
            'organization_id' => $organization->getKey(),
        ], [
            'color' => '#000000',
        ], $externalIdentifier2);

        // Act
        $key1 = $helper->getKeyByExternalIdentifier($externalIdentifier1);
        $key2 = $helper->getKeyByExternalIdentifier($externalIdentifier2);

        // Assert
        $this->assertSame($project->getKey(), $key1);
        $this->assertSame(Project::where('name', '=', 'Not existing project')->first()->getKey(), $key2);
    }

    public function test_get_external_ids_returns_all_external_ids_that_were_temporary_stored_via_get_key(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();
        $externalIdentifier1 = '12345';
        $externalIdentifier2 = '54321';
        $helper = new ImportDatabaseHelper(Project::class, ['name', 'organization_id'], true);
        $helper->getKey([
            'name' => $project->name,
            'organization_id' => $organization->getKey(),
        ], [
            'color' => '#000000',
        ], $externalIdentifier1);
        $helper->getKey([
            'name' => 'Not existing project',
            'organization_id' => $organization->getKey(),
        ], [
            'color' => '#000000',
        ], $externalIdentifier2);

        // Act
        $externalKeys = $helper->getExternalIds();

        // Assert
        $this->assertCount(2, $externalKeys);
        $this->assertContains($externalIdentifier1, $externalKeys);
        $this->assertContains($externalIdentifier2, $externalKeys);
    }
}
