<?php

declare(strict_types=1);

namespace App\Console\Commands\Auth;

use App\Mail\AuthApiTokenExpirationReminderMail;
use App\Mail\AuthApiTokenExpiredMail;
use App\Models\Passport\Token;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class AuthSendReminderForExpiringApiTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:send-mails-expiring-api-tokens '.
        ' { --dry-run : Do not actually send emails or save anything to the database, just output what would happen }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends emails about expiring API tokens, one week before and when they expired.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->comment('Running in dry-run mode. No emails will be sent and nothing will be saved to the database.');
        }

        $this->comment('Sending reminder emails about expiring API tokens...');
        $sentMails = 0;
        Token::query()
            ->where('expires_at', '<=', Carbon::now()->addDays(7))
            ->whereNull('reminder_sent_at')
            ->with([
                'client',
                'user',
            ])
            ->whereHas('user', function (Builder $query): void {
                /** @var Builder<User> $query */
                $query->where('is_placeholder', '=', false);
            })
            ->isApiToken(true)
            ->orderBy('created_at', 'asc')
            ->chunk(500, function (Collection $tokens) use ($dryRun, &$sentMails): void {
                /** @var Collection<int, Token> $tokens */
                foreach ($tokens as $token) {
                    $user = $token->user;
                    $this->info('Start sending email to user "'.$user->email.'" ('.$user->getKey().') reminding about API token '.$token->getKey());
                    $sentMails++;
                    if (! $dryRun) {
                        Mail::to($user->email)
                            ->queue(new AuthApiTokenExpirationReminderMail($token, $user));
                        $token->reminder_sent_at = Carbon::now();
                        $token->save();
                    }
                }
            });
        $this->comment('Finished sending '.$sentMails.' expiring API token emails...');

        $this->comment('Sent emails about expired API tokens');
        $sentMails = 0;
        Token::query()
            ->where('expires_at', '<=', Carbon::now())
            ->whereNull('expired_info_sent_at')
            ->with([
                'client',
                'user',
            ])
            ->whereHas('user', function (Builder $query): void {
                /** @var Builder<User> $query */
                $query->where('is_placeholder', '=', false);
            })
            ->isApiToken(true)
            ->orderBy('created_at', 'asc')
            ->chunk(500, function (Collection $tokens) use ($dryRun, &$sentMails): void {
                /** @var Collection<int, Token> $tokens */
                foreach ($tokens as $token) {
                    $user = $token->user;
                    $this->info('Start sending email to user "'.$user->email.'" ('.$user->getKey().') about expired API token '.$token->getKey());
                    $sentMails++;
                    if (! $dryRun) {
                        Mail::to($user->email)
                            ->queue(new AuthApiTokenExpiredMail($token, $user));
                        $token->expired_info_sent_at = Carbon::now();
                        $token->save();
                    }
                }
            });
        $this->comment('Finished sending '.$sentMails.' expired API token emails...');

        return self::SUCCESS;
    }
}
