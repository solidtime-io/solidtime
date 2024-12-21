<?php

declare(strict_types=1);

namespace App\Console\Commands\Admin;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Console\Command;

class UserVerifyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:user:verify
                { email : The email of the user to verify }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify the email address of an user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        $this->info('Start verifying user with email "'.$email.'"');

        /** @var User|null $user */
        $user = User::query()->where('email', $email)
            ->where('is_placeholder', '=', false)
            ->first();

        if ($user === null) {
            $this->error('User with email "'.$email.'" not found.');

            return self::FAILURE;
        }

        if ($user->hasVerifiedEmail()) {
            $this->info('User with email "'.$email.'" already verified.');

            return self::FAILURE;
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        $this->info('User with email "'.$email.'" has been verified.');

        return self::SUCCESS;
    }
}
