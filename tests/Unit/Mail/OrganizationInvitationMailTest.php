<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(OrganizationInvitationMail::class)]
#[UsesClass(OrganizationInvitationMail::class)]
class OrganizationInvitationMailTest extends TestCaseWithDatabase
{
    public function test_mail_renders_content_correctly(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $invitation = OrganizationInvitation::factory()->forOrganization($organization)->create();
        $mail = new OrganizationInvitationMail($invitation);

        // Act
        $rendered = $mail->render();

        // Assert
        $this->assertStringContainsString('You have been invited to join the '.$invitation->organization->name.' organization', $rendered);
    }
}
