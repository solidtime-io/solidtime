<?php

declare(strict_types=1);

namespace App\Console\Commands\SelfHost;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SelfHostDatabaseConsistency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'self-host:database-consistency';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hadAProblem = false;

        // Task need to be part of project in time entries
        $problems = DB::table('time_entries')
            ->select(['time_entries.id as id'])
            ->join('tasks', 'time_entries.task_id', '=', 'tasks.id')
            ->where('tasks.project_id', '!=', DB::raw('time_entries.project_id'))
            ->get();
        $this->logProblems($problems, 'Time entries have a task that does not belong to the project of the time entry', $hadAProblem);

        // Client id is the client id of the project
        $problems = DB::table('time_entries')
            ->select(['time_entries.id as id'])
            ->join('projects', 'time_entries.project_id', '=', 'projects.id')
            ->where(DB::raw('coalesce(projects.client_id::varchar, \'\')'), '!=', DB::raw('coalesce(time_entries.client_id::varchar, \'\')'))
            ->get();
        $this->logProblems($problems, 'Time entries have a client that does not match the client of the project', $hadAProblem);

        // Client id can only be not null if the project id is not null
        $problems = DB::table('time_entries')
            ->select(['time_entries.id as id'])
            ->whereNotNull('client_id')
            ->whereNull('project_id')
            ->get();
        $this->logProblems($problems, 'Time entries have a client but no project', $hadAProblem);

        // Every user needs to be a member of at least one organization
        $problems = DB::table('users')
            ->select(['users.id as id'])
            ->leftJoin('members', 'users.id', '=', 'members.user_id')
            ->whereNull('members.id')
            ->get();
        $this->logProblems($problems, 'Users are not member of any organization', $hadAProblem);

        // Every organization needs at least an owner
        $problems = DB::table('organizations')
            ->select(['organizations.id as id'])
            ->leftJoin('members', function (JoinClause $join): void {
                $join->on('organizations.id', '=', 'members.organization_id')
                    ->where('members.role', '=', 'owner');
            })
            ->whereNull('members.id')
            ->get();
        $this->logProblems($problems, 'Organizations without an owner', $hadAProblem);

        // Every member can only have one running time entry
        $problems = DB::table('time_entries')
            ->select(['user_id as id'])
            ->whereNull('end')
            ->groupBy('user_id')
            ->havingRaw('count(*) > 1')
            ->get(['user_id', DB::raw('count(*) as count')]);
        $this->logProblems($problems, 'Users with more than one running time entry', $hadAProblem);

        // Users have a current organization that they are not a member of
        $problems = DB::table('users')
            ->select(['users.id as id'])
            ->whereNotNull('current_team_id')
            ->whereNotIn('current_team_id', function (Builder $query): void {
                $query->select('organization_id')
                    ->from('members')
                    ->whereColumn('members.user_id', 'users.id');
            })->get();
        $this->logProblems($problems, 'Users have a current organization that they are not a member of', $hadAProblem);

        return $hadAProblem ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  Collection<int, \stdClass>  $problems
     */
    private function logProblems(Collection $problems, string $message, bool &$hadAProblem): void
    {
        $message = 'Consistency problem: '.$message;
        if ($problems->isNotEmpty()) {
            $ids = $problems->pluck('id');
            $hadAProblem = true;
            Log::error($message, [
                'ids' => $ids,
            ]);

            $error = $message;
            foreach ($ids as $id) {
                $error .= "\n  - ".$id;
            }
            $this->error($error);
        }
    }
}
