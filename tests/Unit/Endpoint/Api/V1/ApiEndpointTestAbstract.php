<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Replaces the temporary URL builder of the private disk to capture the options
     * passed to temporaryUrl. Returns a closure that yields the captured options.
     *
     * @return Closure(): (array<string, mixed>|null)
     */
    protected function captureTemporaryUrlOptions(): Closure
    {
        $captured = null;
        Storage::disk(config('filesystems.private'))->buildTemporaryUrlsUsing(
            function (string $path, DateTimeInterface $expiration, array $options) use (&$captured): string {
                $captured = $options;

                return 'https://storage.fake/'.$path;
            }
        );

        return function () use (&$captured): ?array {
            return $captured;
        };
    }
}
