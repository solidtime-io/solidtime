<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Models\User;
use App\Service\WhitelabelContract;
use Illuminate\Auth\Notifications\ResetPassword;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCaseWithDatabase;

#[UsesClass(WhitelabelContract::class)]
class NotificationEmailTemplateTest extends TestCaseWithDatabase
{
    public function test_salutation_uses_solidtime_by_default(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $rendered = (string) (new ResetPassword('token'))->toMail($user)->render();

        // Assert
        $this->assertStringContainsString('Regards,<br>'."\n".'solidtime', $rendered);
    }

    public function test_salutation_uses_the_whitelabel_name(): void
    {
        // Arrange
        $this->actAsWhitelabeledInstance();
        $user = User::factory()->create();

        // Act
        $rendered = (string) (new ResetPassword('token'))->toMail($user)->render();

        // Assert
        $this->assertStringContainsString('Acme Time', $rendered);
        $this->assertStringContainsString('https://cdn.acme.test/logo.png', $rendered);
    }
}
