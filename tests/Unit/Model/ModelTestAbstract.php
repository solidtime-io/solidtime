<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ModelTestAbstract extends TestCase
{
    use RefreshDatabase;
}
