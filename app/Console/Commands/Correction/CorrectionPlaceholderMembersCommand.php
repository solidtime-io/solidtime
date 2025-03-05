<?php

declare(strict_types=1);

namespace App\Console\Commands\Correction;

use App\Enums\Role;
use App\Models\Member;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class CorrectionPlaceholderMembersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'correction:placeholder-members '.
        ' { --dry-run : Do not actually save anything to the database, just output what would happen }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets all members who belong to a placeholder user to role placeholder';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->comment('Sets all members who belong to a placeholder user to role placeholder...');
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->comment('Running in dry-run mode. Nothing will be saved to the database.');
        }

        $members = Member::query()
            ->where('role', '!=', Role::Placeholder->value)
            ->whereHas('user', function (Builder $builder): void {
                /** @var Builder<User> $builder */
                $builder->where('is_placeholder', '=', true);
            })
            ->get();
        foreach ($members as $member) {
            /** @var Member $member */
            $member->role = Role::Placeholder->value;
            if (! $dryRun) {
                $member->save();
            }
            $this->line('Set role of member (id='.$member->getKey().') to placeholder');
        }

        return self::SUCCESS;
    }
}
