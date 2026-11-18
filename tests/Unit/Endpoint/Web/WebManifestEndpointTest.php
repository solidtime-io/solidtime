<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Http\Controllers\Web\WebManifestController;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(WebManifestController::class)]
class WebManifestEndpointTest extends EndpointTestAbstract
{
    public function test_show_returns_default_manifest(): void
    {
        // Act
        $response = $this->get(route('web-manifest'));

        // Assert
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/manifest+json');
        $response->assertJsonPath('name', 'solidtime');
        $response->assertJsonPath('short_name', 'solidtime');
        $response->assertJsonPath('icons.0.src', '/favicons/android-chrome-192x192.png');
        $response->assertJsonPath('icons.1.src', '/favicons/android-chrome-512x512.png');
        $response->assertJsonPath('theme_color', '#000000');
        $response->assertJsonPath('background_color', '#000000');
    }

    public function test_show_returns_whitelabel_manifest(): void
    {
        // Arrange
        $this->actAsWhitelabeledInstance();

        // Act
        $response = $this->get(route('web-manifest'));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('name', 'Acme Time');
        $response->assertJsonPath('short_name', 'Acme Time');
        $response->assertJsonCount(1, 'icons');
        $response->assertJsonPath('icons.0.src', 'https://cdn.acme.test/favicon.png');
        $response->assertJsonPath('theme_color', '#12ab34');
        $response->assertJsonPath('background_color', '#000000');
    }
}
