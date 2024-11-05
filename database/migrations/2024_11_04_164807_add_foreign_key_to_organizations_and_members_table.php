<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $foreignKeyProblems = DB::table('organizations')
            ->select(['organizations.id', 'organizations.user_id'])
            ->whereNotExists(function (Builder $query): void {
                $query->select('id')
                    ->from('users')
                    ->whereColumn('organizations.user_id', 'users.id');
            })
            ->get();
        foreach ($foreignKeyProblems as $foreignKeyProblem) {
            Log::error('Organization with ID '.$foreignKeyProblem->id.' has non-existing owner with ID '.$foreignKeyProblem->user_id);
        }
        if ($foreignKeyProblems->count() > 0) {
            throw new Exception('There are organizations with non-existing owners, check the logs for more information');
        }
        Schema::table('organizations', function (Blueprint $table): void {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
        $foreignKeyProblems = DB::table('members')
            ->select(['members.id', 'members.organization_id'])
            ->whereNotExists(function (Builder $query): void {
                $query->select('id')
                    ->from('organizations')
                    ->whereColumn('members.organization_id', 'organizations.id');
            })
            ->get();
        foreach ($foreignKeyProblems as $foreignKeyProblem) {
            Log::error('Member with ID '.$foreignKeyProblem->id.' has non-existing organization with ID '.$foreignKeyProblem->organization_id);
        }
        if ($foreignKeyProblems->count() > 0) {
            throw new Exception('There are members with non-existing organizations, check the logs for more information');
        }
        $foreignKeyProblems = DB::table('members')
            ->select(['members.id', 'members.user_id'])
            ->whereNotExists(function (Builder $query): void {
                $query->select('id')
                    ->from('users')
                    ->whereColumn('members.user_id', 'users.id');
            })
            ->get();
        foreach ($foreignKeyProblems as $foreignKeyProblem) {
            Log::error('Member with ID '.$foreignKeyProblem->id.' has non-existing user with ID '.$foreignKeyProblem->user_id);
        }
        if ($foreignKeyProblems->count() > 0) {
            throw new Exception('There are members with non-existing users, check the logs for more information');
        }
        Schema::table('members', function (Blueprint $table): void {
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });
        Schema::table('members', function (Blueprint $table): void {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['user_id']);
        });
    }
};
