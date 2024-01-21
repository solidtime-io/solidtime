<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
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
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Relation::enforceMorphMap([
            'membership' => Membership::class,
            'team' => Organization::class,
            'team_invitation' => OrganizationInvitation::class,
            'user' => User::class,
        ]);
    }
}
