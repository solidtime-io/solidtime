<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Resources\AuditResource;
use App\Models\Audit;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(AuditResource::class)]
class AuditResourceTest extends FilamentTestCase
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

    public function test_can_list_audits(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $timeEntry = TimeEntry::factory()->forMember($user->member)->create();
        DB::table((new Audit())->getTable())->delete();
        $audits = Audit::factory()->auditFor($timeEntry)->auditUser($user->user)->createMany(5);

        // Act
        $response = Livewire::test(AuditResource\Pages\ListAudits::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($audits);
    }

    public function test_can_see_view_page_of_audit(): void
    {
        // Arrange
        DB::table((new Audit())->getTable())->delete();
        $audit = Audit::factory()->create();

        // Act
        $response = Livewire::test(AuditResource\Pages\ViewAudit::class, ['record' => $audit->getKey()]);

        // Assert
        $response->assertSuccessful();
    }
}
