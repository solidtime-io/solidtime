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
            $panel->discoverResources(
                in: module_path($module->getName(), 'app/Filament/Resources'),
                for: 'Extensions\\'.$module->getName().'\\App\\Filament\\Resources'
            );

            $panel->discoverPages(
                in: module_path($module->getName(), 'app/Filament/Pages'),
                for: 'Extensions\\'.$module->getName().'\\App\\Filament\\Pages'
            );

            $panel->discoverWidgets(
                in: module_path($module->getName(), 'app/Filament/Widgets'),
                for: 'Extensions\\'.$module->getName().'\\App\\Filament\\Widgets'
            );
        }

        return $panel;
    }
}
