<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Service\WhitelabelContract;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(WhitelabelContract::class)]
class WhitelabelContractTest extends TestCase
{
    public function test_default_is_the_solidtime_branding(): void
    {
        // Arrange
        $whitelabel = new WhitelabelContract;

        // Act & Assert
        $this->assertSame('solidtime', $whitelabel->getName());
        $this->assertNull($whitelabel->getLogoUrl());
        $this->assertNull($whitelabel->getLogoSvgUrl());
        $this->assertNull($whitelabel->getWebLogoUrl());
        $this->assertNull($whitelabel->getDarkLogoUrl());
        $this->assertNull($whitelabel->getFaviconUrl());
        $this->assertNull($whitelabel->getFaviconPath());
        $this->assertSame('#000000', $whitelabel->getThemeColor());
    }

    public function test_web_logo_prefers_the_svg_logo(): void
    {
        // Arrange
        $withSvg = new class extends WhitelabelContract
        {
            public function getLogoUrl(): ?string
            {
                return 'https://cdn.acme.test/logo.png';
            }

            public function getLogoSvgUrl(): ?string
            {
                return 'https://cdn.acme.test/logo.svg';
            }
        };
        $withoutSvg = new class extends WhitelabelContract
        {
            public function getLogoUrl(): ?string
            {
                return 'https://cdn.acme.test/logo.png';
            }
        };

        // Act & Assert
        $this->assertSame('https://cdn.acme.test/logo.svg', $withSvg->getWebLogoUrl());
        $this->assertSame('https://cdn.acme.test/logo.png', $withoutSvg->getWebLogoUrl());
    }
}
