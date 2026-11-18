<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Service\WhitelabelContract;
use Illuminate\Http\JsonResponse;

class WebManifestController extends Controller
{
    public function show(WhitelabelContract $whitelabel): JsonResponse
    {
        $faviconUrl = $whitelabel->getFaviconUrl();
        $icons = $faviconUrl !== null ? [
            [
                'src' => $faviconUrl,
                'sizes' => 'any',
            ],
        ] : [
            [
                'src' => '/favicons/android-chrome-192x192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
            [
                'src' => '/favicons/android-chrome-512x512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
        ];

        return response()->json([
            'name' => $whitelabel->getName(),
            'short_name' => $whitelabel->getName(),
            'icons' => $icons,
            'theme_color' => $whitelabel->getThemeColor(),
            // Background of the splash screen before the app is loaded, it matches the app and not the brand
            'background_color' => '#000000',
            'display' => 'standalone',
        ], 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }
}
