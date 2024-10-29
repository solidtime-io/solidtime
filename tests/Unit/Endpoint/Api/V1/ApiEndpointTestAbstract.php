<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use Illuminate\Testing\TestResponse;
use Tests\TestCaseWithDatabase;

class ApiEndpointTestAbstract extends TestCaseWithDatabase
{
    protected function assertResponseCode(TestResponse $response, int $statusCode): void
    {
        if ($response->getStatusCode() !== $statusCode) {
            dump($response->getContent());
        }
        $response->assertStatus($statusCode);
    }
}
