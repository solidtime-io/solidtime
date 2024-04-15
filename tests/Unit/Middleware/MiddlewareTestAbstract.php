<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class MiddlewareTestAbstract extends TestCase
{
    use RefreshDatabase;
}
