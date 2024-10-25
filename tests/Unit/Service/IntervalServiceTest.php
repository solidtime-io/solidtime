<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Service\IntervalService;
use Carbon\CarbonInterval;
use Tests\TestCase;

class IntervalServiceTest extends TestCase
{
    public function test_format_returns_correctly_formatted_interval(): void
    {
        // Arrange
        $intervalService = app(IntervalService::class);
        $interval = CarbonInterval::seconds(123456789123);

        // Act
        $result = $intervalService->format($interval);

        // Assert
        $this->assertEquals('34293552:32:03', $result);
    }
}
