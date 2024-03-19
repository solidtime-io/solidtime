<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use Tests\TestCase;

class TimezoneServiceTest extends TestCase
{
    public function test_get_timezones_returns_all_available_timezones(): void
    {
        // Arrange
        $service = new \App\Service\TimezoneService();

        // Act
        $result = $service->getTimezones();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(419, $result);
        $this->assertContains('Europe/Vienna', $result);
        $this->assertContains('Europe/Berlin', $result);
        $this->assertContains('Europe/London', $result);
    }
}
