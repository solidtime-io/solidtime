<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import;

use App\Models\Project;
use App\Models\User;
use App\Service\Import\ImportDatabaseHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
}
