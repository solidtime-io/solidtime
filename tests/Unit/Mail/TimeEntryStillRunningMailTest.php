<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\TimeEntryStillRunningMail;
use App\Models\TimeEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(TimeEntryStillRunningMail::class)]
class TimeEntryStillRunningMailTest extends TestCaseWithDatabase
{
    public function test_mail_renders_content_correctly(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $timeEntry = TimeEntry::factory()->create([
            'description' => 'TEST 123',
        ]);
        $mail = new TimeEntryStillRunningMail($timeEntry, $user->user);

        // Act
        $rendered = $mail->render();

        // Assert
        $this->assertStringContainsString('Your currently running time entry "TEST 123"', $rendered);
    }

    public function test_mail_uses_solidtime_branding_by_default(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $timeEntry = TimeEntry::factory()->create();
        $mail = new TimeEntryStillRunningMail($timeEntry, $user->user);

        // Act
        $rendered = $mail->render();

        // Assert
        $this->assertStringContainsString('images/solidtime-logo.png', $rendered);
        $this->assertStringContainsString('Go to solidtime', $rendered);
    }

    public function test_mail_uses_whitelabel_name_and_logo(): void
    {
        // Arrange
        $this->actAsWhitelabeledInstance();
        $user = $this->createUserWithPermission();
        $timeEntry = TimeEntry::factory()->create();
        $mail = new TimeEntryStillRunningMail($timeEntry, $user->user);

        // Act
        $rendered = $mail->render();

        // Assert
        $this->assertStringContainsString('src="https://cdn.acme.test/logo.png"', $rendered);
        $this->assertStringContainsString('srcset="https://cdn.acme.test/logo.svg"', $rendered);
        $this->assertStringContainsString('Go to Acme Time', $rendered);
        $this->assertStringContainsString('Acme Time. All rights reserved.', $rendered);
        $this->assertStringNotContainsString('solidtime-logo', $rendered);
    }
}
