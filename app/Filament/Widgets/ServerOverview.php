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
        $currentVersion = Cache::get('latest_version', null);

        $needsUpdate = false;
        if ($currentVersion !== null && version_compare($currentVersion, config('app.version')) > 0) {
            $needsUpdate = true;
        }

        return [
            'version' => config('app.version'),
            'build' => config('app.build'),
            'environment' => config('app.env'),
            'currentVersion' => $currentVersion,
            'needsUpdate' => $needsUpdate,
        ];
    }
}
