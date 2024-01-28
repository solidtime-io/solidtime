<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Client;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Relation::enforceMorphMap([
            'membership' => Membership::class,
            'organization' => Organization::class,
            'organization-invitation' => OrganizationInvitation::class,
            'user' => User::class,
            'time-entry' => TimeEntry::class,
            'project' => Project::class,
            'task' => Task::class,
            'client' => Client::class,
            'tag' => Tag::class,
        ]);
        Model::unguard();
        Section::configureUsing(function (Section $section): void {
            $section->columns(1);
        }, null, true);
    }
}
