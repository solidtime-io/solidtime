<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\ReportResource;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(ReportResource::class)]
class ReportResourceTest extends FilamentTestCase
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

    public function test_can_list_reports(): void
    {
        // Arrange
        $reports = Report::factory()->createMany(5);

        // Act
        $response = Livewire::test(ReportResource\Pages\ListReports::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($reports);
    }

    public function test_can_see_edit_page_of_report(): void
    {
        // Arrange
        $report = Report::factory()->create();

        // Act
        $response = Livewire::test(ReportResource\Pages\EditReport::class, [
            'record' => $report->getKey(),
        ]);

        // Assert
        $response->assertSuccessful();
    }
}
