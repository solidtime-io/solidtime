<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Auth;

use App\Console\Commands\Auth\AuthSendReminderForExpiringApiTokensCommand;
use App\Mail\AuthApiTokenExpirationReminderMail;
use App\Mail\AuthApiTokenExpiredMail;
use App\Models\Passport\Client;
use App\Models\Passport\Token;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(AuthSendReminderForExpiringApiTokensCommand::class)]
class AuthSendReminderForExpiringApiTokensCommandTest extends TestCaseWithDatabase
{
    public function test_sends_mail_for_expired_api_tokens_but_ignores_the_one_where_the_mail_was_already_sent_and_ignores_non_api_tokens(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $apiClient = Client::factory()->apiClient()->create();
        $otherClient = Client::factory()->desktopClient()->create();
        $expiredToken = Token::factory()->forUser($user->user)->forClient($apiClient)->create([
            'reminder_sent_at' => Carbon::now()->subDays(8),
            'expired_info_sent_at' => null,
            'expires_at' => Carbon::now()->subDay(),
        ]);
        $expiredTokenWithMailSent = Token::factory()->forUser($user->user)->forClient($apiClient)->create([
            'reminder_sent_at' => Carbon::now()->subDays(8),
            'expired_info_sent_at' => Carbon::now(),
            'expires_at' => Carbon::now()->subDay(),
        ]);
        $nonApiToken = Token::factory()->forUser($user->user)->forClient($otherClient)->create([
            'reminder_sent_at' => null,
            'expired_info_sent_at' => null,
            'expires_at' => Carbon::now()->subDay(),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('auth:send-mails-expiring-api-tokens');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $expiredToken->refresh();
        $expiredTokenWithMailSent->refresh();
        $nonApiToken->refresh();
        $this->assertNotNull($expiredToken->expired_info_sent_at);
        $this->assertNotNull($expiredTokenWithMailSent->expired_info_sent_at);
        $this->assertNull($nonApiToken->reminder_sent_at);
        $this->assertNull($nonApiToken->expired_info_sent_at);
        Mail::assertNotQueued(AuthApiTokenExpirationReminderMail::class);
        Mail::assertQueued(AuthApiTokenExpiredMail::class, function (AuthApiTokenExpiredMail $mail) use ($user, $expiredToken): bool {
            return $mail->hasTo($user->user->email) &&
                $mail->token->is($expiredToken) &&
                $mail->user->is($user->user);
        });

        $output = Artisan::output();
        $this->assertStringContainsString('Finished sending 0 expiring API token emails...', $output);
        $this->assertStringContainsString('Finished sending 1 expired API token emails...', $output);
        $this->assertStringContainsString(
            'Start sending email to user "'.$user->user->email.'" ('.
            $user->user->id.') about expired API token '.$expiredToken->getKey(), $output);
    }

    public function test_sends_mail_for_api_tokens_that_expire_soon_but_ignores_the_one_where_the_mail_was_already_sent_and_ignores_non_api_tokens(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $apiClient = Client::factory()->apiClient()->create();
        $otherClient = Client::factory()->desktopClient()->create();
        $expiringToken = Token::factory()->forUser($user->user)->forClient($apiClient)->create([
            'reminder_sent_at' => null,
            'expired_info_sent_at' => null,
            'expires_at' => Carbon::now()->addDays(6),
        ]);
        $expiringTokenWithMailSent = Token::factory()->forUser($user->user)->forClient($apiClient)->create([
            'reminder_sent_at' => Carbon::now(),
            'expired_info_sent_at' => null,
            'expires_at' => Carbon::now()->addDays(6),
        ]);
        $nonApiToken = Token::factory()->forUser($user->user)->forClient($otherClient)->create([
            'reminder_sent_at' => null,
            'expired_info_sent_at' => null,
            'expires_at' => Carbon::now()->addDays(6),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('auth:send-mails-expiring-api-tokens');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $expiringToken->refresh();
        $expiringTokenWithMailSent->refresh();
        $nonApiToken->refresh();
        $this->assertNotNull($expiringToken->reminder_sent_at);
        $this->assertNull($expiringToken->expired_info_sent_at);
        $this->assertNotNull($expiringTokenWithMailSent->reminder_sent_at);
        $this->assertNull($expiringTokenWithMailSent->expired_info_sent_at);
        $this->assertNull($nonApiToken->reminder_sent_at);
        $this->assertNull($nonApiToken->expired_info_sent_at);
        Mail::assertNotQueued(AuthApiTokenExpiredMail::class);
        Mail::assertQueued(AuthApiTokenExpirationReminderMail::class, function (AuthApiTokenExpirationReminderMail $mail) use ($user, $expiringToken): bool {
            return $mail->hasTo($user->user->email) &&
                $mail->token->is($expiringToken) &&
                $mail->user->is($user->user);
        });

        $output = Artisan::output();
        $this->assertStringContainsString('Finished sending 1 expiring API token emails...', $output);
        $this->assertStringContainsString('Finished sending 0 expired API token emails...', $output);
        $this->assertStringContainsString(
            'Start sending email to user "'.$user->user->email.'" ('.
            $user->user->id.') reminding about API token '.$expiringToken->getKey(), $output);
    }
}
