<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\ExportResource;
use App\Models\Export;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(ExportResource::class)]
class ExportResourceTest extends FilamentTestCase
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

    public function test_can_list_exports(): void
    {
        // Arrange
        $exports = collect([
            Export::factory()->create(),
            Export::factory()->running()->create(),
            Export::factory()->completed()->create(),
            Export::factory()->failed()->create(),
        ]);

        // Act
        $response = Livewire::test(ExportResource\Pages\ListExports::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($exports);
    }

    public function test_can_see_view_page_of_export(): void
    {
        // Arrange
        $export = Export::factory()->completed()->create();

        // Act
        $response = Livewire::test(ExportResource\Pages\ViewExport::class, [
            'record' => $export->getKey(),
        ]);

        // Assert
        $response->assertSuccessful();
    }
}
