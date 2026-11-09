<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\AuthApiTokenCreatedMail;
use App\Models\Passport\Client;
use App\Models\Passport\Token;
use App\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(AuthApiTokenCreatedMail::class)]
class AuthApiTokenCreatedMailTest extends TestCaseWithDatabase
{
    public function test_mail_renders_content_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $client = Client::factory()->apiClient()->create();
        $token = Token::factory()->forClient($client)->forUser($user)->create([
            'name' => 'TEST',
        ]);
        $mail = new AuthApiTokenCreatedMail($token, $user);

        // Act
        $rendered = $mail->render();

        // Assert
        $this->assertStringContainsString('A new API token "TEST" was created for your account.', $rendered);
        $this->assertSame('A new API token was created for your account', $mail->subject);
    }
}
