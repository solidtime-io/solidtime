<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Widgets\ActiveUserOverview;
use App\Filament\Widgets\ServerOverview;
use App\Filament\Widgets\TimeEntriesCreated;
use App\Filament\Widgets\TimeEntriesImported;
use App\Filament\Widgets\UserRegistrations;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\App;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Laravel\Module as LaravelModule;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel->default()
            ->id('admin')
            ->path('admin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                ServerOverview::class,
                ActiveUserOverview::class,
                UserRegistrations::class,
                TimeEntriesCreated::class,
                TimeEntriesImported::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->plugins([
                EnvironmentIndicatorPlugin::make()
                    ->color(fn () => match (App::environment()) {
                        'production' => null,
                        'staging' => Color::Orange,
                        default => Color::Blue,
                    }),
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Timetracking'),
                NavigationGroup::make()
                    ->label('Users')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('System')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Auth')
                    ->collapsed(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        $modules = Module::allEnabled();

        foreach ($modules as $module) {
            $moduleNamespace = $this->getModuleAppNamespace($module);

            $panel->discoverResources(
                in: module_path($module->getName(), 'app/Filament/Resources'),
                for: $moduleNamespace.'\\Filament\\Resources'
            );

            $panel->discoverPages(
                in: module_path($module->getName(), 'app/Filament/Pages'),
                for: $moduleNamespace.'\\Filament\\Pages'
            );

            $panel->discoverWidgets(
                in: module_path($module->getName(), 'app/Filament/Widgets'),
                for: $moduleNamespace.'\\Filament\\Widgets'
            );
        }

        return $panel;
    }

    /** @var array<string, string> Cache of module name => resolved app namespace. */
    private static array $moduleAppNamespaces = [];

    private function getModuleAppNamespace(LaravelModule $module): string
    {
        return self::$moduleAppNamespaces[$module->getName()] ??= $this->resolveModuleAppNamespace($module);
    }

    /**
     * Resolve the PHP namespace mapped to a module's app/ directory so the
     * Filament panel can discover its Resources/Pages/Widgets under the right
     * namespace.
     *
     * Two module layouts currently coexist in this repo:
     *   - laravel-modules v12 (app_folder enabled): a bare namespace maps to
     *     app/ — e.g. "Extensions\SSO\" => app/, so classes are
     *     Extensions\SSO\Filament\... (this is the current convention).
     *   - the older layout: an "...\App" namespace maps to app/ — e.g.
     *     "Extensions\Billing\App\" => app/, so classes are
     *     Extensions\Billing\App\Filament\...
     *
     * The package's own namespace derivation assumes the v12 (bare) layout and
     * would mis-resolve the legacy modules, so we read each module's composer
     * PSR-4 map and use whichever namespace actually points at app/. The legacy
     * "...\App" shape is only a fallback for when composer is missing/unreadable.
     * Once every module adopts the bare layout this collapses to
     * config('modules.namespace').'\\'.$module->getName().
     */
    private function resolveModuleAppNamespace(LaravelModule $module): string
    {
        $fallback = 'Extensions\\'.$module->getName().'\\App';

        $composerPath = module_path($module->getName(), 'composer.json');
        $psr4 = [];
        if (is_file($composerPath)) {
            $composer = json_decode((string) file_get_contents($composerPath), true);
            $psr4 = is_array($composer) ? ($composer['autoload']['psr-4'] ?? []) : [];
        }

        foreach ((array) $psr4 as $namespace => $path) {
            if (is_string($namespace) && $this->normalizeComposerPath($path) === 'app') {
                return rtrim($namespace, '\\');
            }
        }

        return $fallback;
    }

    private function normalizeComposerPath(mixed $path): string
    {
        return trim(str_replace('\\', '/', (string) $path), '/');
    }
}
