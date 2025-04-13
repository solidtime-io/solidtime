<?php

declare(strict_types=1);

namespace App\Console\Commands\Admin;

use App\Enums\Weekday;
use App\Models\Organization;
use App\Models\User;
use App\Service\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use LogicException;

class UserCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:user:create
                { name : The name of the user }
                { email : The email of the user }
                { --ask-for-password : Ask for the password, otherwise the command will generate a random one }
                { --verify-email : Verify the email address of the user }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $askForPassword = (bool) $this->option('ask-for-password');
        $verifyEmail = (bool) $this->option('verify-email');

        if (User::query()->where('email', $email)->where('is_placeholder', '=', false)->exists()) {
            $this->error('User with email "'.$email.'" already exists.');

            return self::FAILURE;
        }

        if ($askForPassword) {
            $outputPassword = false;
            $password = $this->secret('Enter the password');
        } else {
            $outputPassword = true;
            $password = bin2hex(random_bytes(16));
        }

        $user = null;
        DB::transaction(function () use (&$user, $name, $email, $password, $verifyEmail): void {
            $user = app(UserService::class)->createUser(
                $name,
                $email,
                $password,
                'UTC',
                Weekday::Monday,
                null,
                verifyEmail: $verifyEmail
            );
        });
        /** @var Organization|null $organization */
        $organization = $user->ownedTeams->first();
        if ($organization === null) {
            throw new LogicException('User does not have an organization');
        }

        $this->info('Created user "'.$name.'" ("'.$email.'")');
        $this->line('ID: '.$user->getKey());
        $this->line('Name: '.$name);
        $this->line('Email: '.$email);
        if ($outputPassword) {
            $this->line('Password: '.$password);
        }
        $this->line('Timezone: '.$user->timezone);
        $this->line('Week start: '.$user->week_start->value);

        // Organization
        $this->line('Currency: '.$organization->currency);

        return self::SUCCESS;
    }
}
