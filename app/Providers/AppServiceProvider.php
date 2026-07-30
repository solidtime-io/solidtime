<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Client;
use App\Models\FailedJob;
use App\Models\Member;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Passport\Token;
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
use DateTimeInterface;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\SecuritySchemes\OAuthFlow;
use Filament\Forms\Components\Section;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $this->app->scoped(PermissionStore::class, function (Application $app): PermissionStore {
            return new PermissionStore;
        });

        // Extensions
        $this->app->bind(IpLookupServiceContract::class, NoIpLookupService::class);
        $this->app->bind(BillingContract::class);

        // Storage
        // The local driver ignores the ResponseContentDisposition option of temporaryUrl,
        // so mirror it through the signed query parameters of the storage route.
        $privateDisk = config('filesystems.private');
        if (config('filesystems.disks.'.$privateDisk.'.driver') === 'local') {
            $disk = Storage::disk($privateDisk);
            $disk->serveUsing(function (Request $request, string $path, array $headers) use ($disk): StreamedResponse {
                return $disk->response($path, null, $headers, $request->query('disposition', 'inline'));
            });
            $disk->buildTemporaryUrlsUsing(function (string $path, DateTimeInterface $expiration, array $options) use ($privateDisk): string {
                $parameters = array_filter([
                    'path' => $path,
                    'disposition' => isset($options['ResponseContentDisposition'])
                        ? Str::before($options['ResponseContentDisposition'], ';')
                        : null,
                ]);

                return url(URL::temporarySignedRoute('storage.'.$privateDisk, $expiration, $parameters, absolute: false));
            });
        }

        // Routing
        Route::model('member', Member::class);
        Route::model('invitation', OrganizationInvitation::class);
        Route::model('apiToken', Token::class);
    }
}
