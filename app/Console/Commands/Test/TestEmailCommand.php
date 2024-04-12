<?php

declare(strict_types=1);

namespace App\Console\Commands\Test;

use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email { email : Email address to send the email to }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This test command sends an email.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        Mail::raw('Hello World!', function (Message $message) use ($email): void {
            $message->to($email)
                ->subject('Test Email')
                ->html('<h1>Hello World!</h1>');
        });

        return self::SUCCESS;
    }
}
