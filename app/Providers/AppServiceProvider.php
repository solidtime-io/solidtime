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
use App\Service\PermissionStore;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\SecuritySchemes\OAuthFlow;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\App;
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

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());
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

        Scramble::extendOpenApi(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::oauth2()
                    ->flow('authorizationCode', function (OAuthFlow $flow) {
                        $flow
                            ->authorizationUrl('https://solidtime.test/oauth/authorize');
                    })
            );
        });

        if (config('app.force_https', false) || App::isProduction()) {
            URL::forceScheme('https');
            request()->server->set('HTTPS', request()->header('X-Forwarded-Proto', 'https') === 'https' ? 'on' : 'off');
        }

        $this->app->scoped(PermissionStore::class, function (Application $app): PermissionStore {
            return new PermissionStore();
        });

        Route::model('member', Membership::class);
        Route::model('invitation', OrganizationInvitation::class);
    }
}
