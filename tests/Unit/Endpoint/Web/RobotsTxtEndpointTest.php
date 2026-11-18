<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Http\Controllers\Web\RobotsTxtController;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RobotsTxtController::class)]
class RobotsTxtEndpointTest extends EndpointTestAbstract
{
    public function test_show_denies_all_crawlers_by_default(): void
    {
        // Act
        $response = $this->get(route('robots-txt'));

        // Assert
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame("User-agent: *\nDisallow: /\n", $response->getContent());
    }

    public function test_show_allows_all_crawlers_if_configured(): void
    {
        // Arrange
        config(['app.robots_txt' => 'allow']);

        // Act
        $response = $this->get(route('robots-txt'));

        // Assert
        $response->assertOk();
        $this->assertSame("User-agent: *\nDisallow:\n", $response->getContent());
    }

    public function test_show_denies_all_crawlers_if_configured_value_is_invalid(): void
    {
        // Arrange
        config(['app.robots_txt' => 'something-else']);

        // Act
        $response = $this->get(route('robots-txt'));

        // Assert
        $response->assertOk();
        $this->assertSame("User-agent: *\nDisallow: /\n", $response->getContent());
    }
}
