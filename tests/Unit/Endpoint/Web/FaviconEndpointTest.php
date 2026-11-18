<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Http\Controllers\Web\FaviconController;
use App\Service\WhitelabelContract;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FaviconController::class)]
class FaviconEndpointTest extends EndpointTestAbstract
{
    public function test_serves_the_solidtime_favicon_directly_by_default(): void
    {
        // Act
        $response = $this->get('/favicon.ico');

        // Assert
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/x-icon');
        $response->assertHeader('Cache-Control', 'max-age=86400, public');
        $response->assertCookieMissing(config('session.cookie'));
        $this->assertSame(file_get_contents(public_path('favicons/favicon.ico')), $response->streamedContent());
    }

    public function test_redirects_to_a_whitelabel_favicon_on_an_external_url(): void
    {
        // Arrange
        $this->actAsWhitelabeledInstance();

        // Act
        $response = $this->get('/favicon.ico');

        // Assert
        $response->assertRedirect('https://cdn.acme.test/favicon.png');
    }

    public function test_serves_a_whitelabel_favicon_from_the_public_disk_directly(): void
    {
        // Arrange
        $this->mockPublicStorage();
        $png = (string) file_get_contents(public_path('favicons/favicon-32x32.png'));
        Storage::disk(config('filesystems.public'))->put('whitelabel/favicon.png', $png);
        $this->mockWhitelabelFaviconPath('whitelabel/favicon.png');

        // Act
        $response = $this->get('/favicon.ico');

        // Assert
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('Cache-Control', 'max-age=86400, public');
        $this->assertSame($png, $response->getContent());
    }

    public function test_serves_the_solidtime_favicon_if_the_whitelabel_favicon_file_is_missing(): void
    {
        // Arrange
        $this->mockPublicStorage();
        $this->mockWhitelabelFaviconPath('whitelabel/missing.png');

        // Act
        $response = $this->get('/favicon.ico');

        // Assert
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/x-icon');
    }

    private function mockWhitelabelFaviconPath(string $path): void
    {
        $this->mock(WhitelabelContract::class, function (MockInterface $mock) use ($path): void {
            $mock->shouldReceive('getFaviconPath')->andReturn($path);
            $mock->shouldReceive('getFaviconUrl')->andReturn('https://storage.acme.test/'.$path);
        });
    }
}
