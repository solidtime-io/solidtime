<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Mockery;
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
     * Captures the options passed to temporaryUrl on the private disk.
     *
     * @return Closure(): (array<string, mixed>|null)
     */
    protected function captureTemporaryUrlOptions(): Closure
    {
        $captured = null;
        $diskName = config('filesystems.private');
        $disk = Mockery::mock(Storage::disk($diskName))->makePartial();
        $disk->shouldReceive('temporaryUrl')->andReturnUsing(
            function (string $path, DateTimeInterface $expiration, array $options) use (&$captured): string {
                $captured = $options;

                return 'https://storage.fake/'.$path;
            }
        );
        Storage::set($diskName, $disk);

        return function () use (&$captured): ?array {
            return $captured;
        };
    }
}
