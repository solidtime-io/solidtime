<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Dto;

use App\Service\Dto\UserAgentDto;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(UserAgentDto::class)]
class UserAgentDtoTest extends TestCase
{
    public function test_chrome_on_windows_is_detected_as_a_desktop_browser(): void
    {
        // Arrange
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $agent = new UserAgentDto;
        $agent->setUserAgent($userAgent);

        // Act
        $platform = $agent->platform();
        $browser = $agent->browser();
        $isDesktop = $agent->isDesktop();

        // Assert
        $this->assertSame('Windows', $platform);
        $this->assertSame('Chrome', $browser);
        $this->assertTrue($isDesktop);
    }

    public function test_edge_is_detected_before_chrome(): void
    {
        // Arrange
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0';
        $agent = new UserAgentDto;
        $agent->setUserAgent($userAgent);

        // Act
        $browser = $agent->browser();

        // Assert
        $this->assertSame('Edge', $browser);
    }

    public function test_iphone_safari_is_detected_as_a_non_desktop_browser(): void
    {
        // Arrange
        $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
        $agent = new UserAgentDto;
        $agent->setUserAgent($userAgent);

        // Act
        $platform = $agent->platform();
        $browser = $agent->browser();
        $isDesktop = $agent->isDesktop();

        // Assert
        $this->assertSame('iOS', $platform);
        $this->assertSame('Safari', $browser);
        $this->assertFalse($isDesktop);
    }

    public function test_ipad_is_detected_as_non_desktop(): void
    {
        // Arrange
        $userAgent = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
        $agent = new UserAgentDto;
        $agent->setUserAgent($userAgent);

        // Act
        $isDesktop = $agent->isDesktop();

        // Assert
        $this->assertFalse($isDesktop);
    }

    public function test_unknown_user_agent_has_no_platform_or_browser_and_is_a_desktop(): void
    {
        // Arrange
        $agent = new UserAgentDto;
        $agent->setUserAgent('CustomClient/1.0');

        // Act
        $platform = $agent->platform();
        $browser = $agent->browser();
        $isDesktop = $agent->isDesktop();

        // Assert
        $this->assertNull($platform);
        $this->assertNull($browser);
        $this->assertTrue($isDesktop);
    }

    public function test_cloudfront_desktop_header_is_detected_as_desktop(): void
    {
        // Arrange
        $agent = new UserAgentDto;
        $agent->setUserAgent('Amazon CloudFront');
        $agent->setHttpHeaders([
            'HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER' => 'true',
        ]);

        // Act
        $isDesktop = $agent->isDesktop();

        // Assert
        $this->assertTrue($isDesktop);
    }

    public function test_cached_values_are_resolved_for_the_current_user_agent(): void
    {
        // Arrange
        $agent = new UserAgentDto;
        $agent->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36');
        $agent->platform();
        $agent->browser();
        $agent->isDesktop();
        $agent->setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Version/17.0 Mobile/15E148 Safari/604.1');

        // Act
        $platform = $agent->platform();
        $browser = $agent->browser();
        $isDesktop = $agent->isDesktop();

        // Assert
        $this->assertSame('iOS', $platform);
        $this->assertSame('Safari', $browser);
        $this->assertFalse($isDesktop);
    }
}
