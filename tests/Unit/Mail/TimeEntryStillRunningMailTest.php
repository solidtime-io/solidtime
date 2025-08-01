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
}
