<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(OrganizationInvitationMail::class)]
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

    public function test_mail_renders_markdown_in_organization_name_as_plain_text(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'name' => 'Organization [Click here](https://example.com) ![image](https://example.com/image.png) <a href="https://example.com">link</a>',
        ]);
        $invitation = OrganizationInvitation::factory()->forOrganization($organization)->create();
        $mail = new OrganizationInvitationMail($invitation);

        // Act
        $rendered = $mail->render();

        // Assert
        $this->assertStringContainsString('[Click here](https://example.com)', $rendered);
        $this->assertStringContainsString('![image](https://example.com/image.png)', $rendered);
        $this->assertStringContainsString('&lt;a href="https://example.com"&gt;link&lt;/a&gt;', $rendered);
        $this->assertDoesNotMatchRegularExpression('/<a [^>]*href="https:\/\/example\.com"/', $rendered);
        $this->assertDoesNotMatchRegularExpression('/<img [^>]*src="https:\/\/example\.com\/image\.png"/', $rendered);
    }
}
