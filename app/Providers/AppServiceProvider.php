<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Client;
use App\Models\FailedJob;
use App\Models\Member;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\BillingContract;
use App\Service\IpLookup\IpLookupServiceContract;
use App\Service\IpLookup\NoIpLookupService;
use App\Service\PermissionStore;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\SecuritySchemes\OAuthFlow;
use Filament\Forms\Components\Section;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
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

        // Eloquent
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());
        Relation::enforceMorphMap([
            'client' => Client::class,
            'failed-job' => FailedJob::class,
            'membership' => Member::class,
            'organization' => Organization::class,
            'organization-invitation' => OrganizationInvitation::class,
            'project' => Project::class,
            'project-member' => ProjectMember::class,
            'tag' => Tag::class,
            'task' => Task::class,
            'time-entry' => TimeEntry::class,
            'user' => User::class,
        ]);
        Model::unguard();

        // Filament
        Section::configureUsing(function (Section $section): void {
            $section->columns(1);
        }, null, true);
        Table::configureUsing(function (Table $table): void {
            $table->paginated([10, 25, 50, 100]);
        });

        // Scramble
        Scramble::extendOpenApi(function (OpenApi $openApi): void {
            $openApi->secure(
                SecurityScheme::oauth2()
                    ->flow('authorizationCode', function (OAuthFlow $flow): void {
                        $flow
                            ->authorizationUrl('https://solidtime.test/oauth/authorize');
                    })
            );
        });

        if (config('app.force_https', false)) {
            URL::forceScheme('https');
            request()->server->set('HTTPS', 'on');
            request()->server->set('X-Forward-Proto', 'https');
        }

        $this->app->scoped(PermissionStore::class, function (Application $app): PermissionStore {
            return new PermissionStore;
        });

        // Extensions
        $this->app->bind(IpLookupServiceContract::class, NoIpLookupService::class);
        $this->app->bind(BillingContract::class);

        // Routing
        Route::model('member', Member::class);
        Route::model('invitation', OrganizationInvitation::class);
    }
}
