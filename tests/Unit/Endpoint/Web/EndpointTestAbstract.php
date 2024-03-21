<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class EndpointTestAbstract extends TestCase
{
    use RefreshDatabase;
}
