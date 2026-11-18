<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Tests\TestCaseWithDatabase;

class LowercaseOrganizationInvitationEmailsMigrationTest extends TestCaseWithDatabase
{
    private function runMigration(): void
    {
        $migration = require database_path('migrations/2026_11_18_000001_lowercase_organization_invitation_emails.php');
        assert($migration instanceof Migration && method_exists($migration, 'up'));
        $migration->up();
    }

    public function test_lowercases_invitation_emails(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $invitation = OrganizationInvitation::factory()->forOrganization($organization)->create([
            'email' => 'Mixed.Case@Email.test',
        ]);
        $lowercaseInvitation = OrganizationInvitation::factory()->forOrganization($organization)->create([
            'email' => 'lower@email.test',
        ]);

        // Act
        $this->runMigration();

        // Assert
        $this->assertSame('mixed.case@email.test', $invitation->refresh()->email);
        $this->assertSame('lower@email.test', $lowercaseInvitation->refresh()->email);
    }

    public function test_keeps_the_accepted_invitation_if_emails_of_the_same_organization_only_differ_in_case(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $acceptedInvitation = OrganizationInvitation::factory()->forOrganization($organization)->accepted()->create([
            'email' => 'User@Email.test',
            'created_at' => Carbon::now()->subDay(),
        ]);
        $newerPendingInvitation = OrganizationInvitation::factory()->forOrganization($organization)->create([
            'email' => 'user@email.test',
            'created_at' => Carbon::now(),
        ]);

        // Act
        $this->runMigration();

        // Assert
        $this->assertDatabaseMissing(OrganizationInvitation::class, ['id' => $newerPendingInvitation->getKey()]);
        $this->assertSame('user@email.test', $acceptedInvitation->refresh()->email);
    }

    public function test_keeps_the_newest_invitation_if_none_of_the_duplicates_is_accepted(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $olderInvitation = OrganizationInvitation::factory()->forOrganization($organization)->create([
            'email' => 'user@email.test',
            'created_at' => Carbon::now()->subDay(),
        ]);
        $newerInvitation = OrganizationInvitation::factory()->forOrganization($organization)->create([
            'email' => 'USER@email.test',
            'created_at' => Carbon::now(),
        ]);

        // Act
        $this->runMigration();

        // Assert
        $this->assertDatabaseMissing(OrganizationInvitation::class, ['id' => $olderInvitation->getKey()]);
        $this->assertSame('user@email.test', $newerInvitation->refresh()->email);
    }

    public function test_keeps_invitations_with_the_same_email_in_different_organizations(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $invitation = OrganizationInvitation::factory()->forOrganization($organization)->create([
            'email' => 'User@Email.test',
        ]);
        $otherInvitation = OrganizationInvitation::factory()->forOrganization($otherOrganization)->create([
            'email' => 'user@email.test',
        ]);

        // Act
        $this->runMigration();

        // Assert
        $this->assertSame('user@email.test', $invitation->refresh()->email);
        $this->assertSame('user@email.test', $otherInvitation->refresh()->email);
    }
}
