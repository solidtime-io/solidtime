<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\VerifyUpdatedEmailMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(VerifyUpdatedEmailMail::class)]
class VerifyUpdatedEmailMailTest extends TestCaseWithDatabase
{
    public function test_mail_renders_content_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $mail = new VerifyUpdatedEmailMail($user, 'New.Email@Example.com');

        // Act
        $rendered = $mail->render();

        // Assert
        $this->assertEquals('new.email@example.com', $mail->email);
        $this->assertStringContainsString('Please verify your new email address', $rendered);
    }

    public function test_mail_uses_relative_signed_verification_url(): void
    {
        // Arrange
        Carbon::setTestNow('2026-05-21 12:00:00');
        $user = User::factory()->create();
        $mail = new VerifyUpdatedEmailMail($user, 'new.email@example.com');

        // Act
        $rendered = $mail->render();
        $expectedPath = URL::temporarySignedRoute(
            'users.verify-email-change',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'user' => $user->getKey(),
                'email' => 'new.email@example.com',
            ],
            false
        );

        // Assert
        $this->assertStringContainsString(e(URL::to($expectedPath)), $rendered);
    }
}
