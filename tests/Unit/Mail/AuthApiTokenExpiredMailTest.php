<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\AuthApiTokenExpiredMail;
use App\Models\Passport\Client;
use App\Models\Passport\Token;
use App\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(AuthApiTokenExpiredMail::class)]
class AuthApiTokenExpiredMailTest extends TestCaseWithDatabase
{
    public function test_mail_renders_content_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $client = Client::factory()->apiClient()->create();
        $token = Token::factory()->forClient($client)->forUser($user)->create([
            'name' => 'TEST',
        ]);
        $mail = new AuthApiTokenExpiredMail($token, $user);

        // Act
        $rendered = $mail->render();

        // Assert
        $this->assertStringContainsString('The API token "TEST" will expire in 7 days!', $rendered);
    }
}
