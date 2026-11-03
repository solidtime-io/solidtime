<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\ImportResource;
use App\Models\Import;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(ImportResource::class)]
class ImportResourceTest extends FilamentTestCase
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

    public function test_can_list_imports(): void
    {
        // Arrange
        $imports = collect([
            Import::factory()->create(),
            Import::factory()->running()->create(),
            Import::factory()->completed()->create(),
            Import::factory()->failed()->create(),
        ]);

        // Act
        $response = Livewire::test(ImportResource\Pages\ListImports::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($imports);
    }

    public function test_can_see_view_page_of_import(): void
    {
        // Arrange
        $import = Import::factory()->completed()->create();

        // Act
        $response = Livewire::test(ImportResource\Pages\ViewImport::class, [
            'record' => $import->getKey(),
        ]);

        // Assert
        $response->assertSuccessful();
    }
}
