<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use Filament\Facades\Filament;
use Tests\TestCaseWithDatabase;

abstract class FilamentTestCase extends TestCaseWithDatabase
{
    protected function setUp(): void
    {
        parent::setUp();
        Filament::setServingStatus();
    }
}
