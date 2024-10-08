<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class ServerOverview extends Widget
{
    protected static string $view = 'filament.widgets.server-overview';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var string|null $currentVersion */
        $currentVersion = config('app.version');
        /** @var string|null $build */
        $build = config('app.build');
        $latestVersion = Cache::get('latest_version', null);

        $needsUpdate = false;
        if ($latestVersion !== null && $currentVersion !== null && version_compare($latestVersion, $currentVersion) > 0) {
            $needsUpdate = true;
        }

        return [
            'version' => $currentVersion,
            'build' => $build,
            'environment' => config('app.env'),
            'currentVersion' => $latestVersion,
            'needsUpdate' => $needsUpdate,
        ];
    }
}
