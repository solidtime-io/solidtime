<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCaseWithDatabase;

abstract class MiddlewareTestAbstract extends TestCaseWithDatabase
{
    use RefreshDatabase;
}
