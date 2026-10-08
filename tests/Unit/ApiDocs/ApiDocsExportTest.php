<?php

declare(strict_types=1);

namespace Tests\Unit\ApiDocs;

use App\Http\Controllers\Api\FallbackController;
use Dedoc\Scramble\Infer\Context;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(FallbackController::class)]
class ApiDocsExportTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        // Scramble keeps its inference context in a static property, which would otherwise outlive the refreshed application
        Context::reset();
        $this->path = storage_path('framework/testing/api-docs-'.uniqid().'.json');
        File::ensureDirectoryExists(dirname($this->path));
    }

    protected function tearDown(): void
    {
        File::delete($this->path);
        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function exportApiDocs(): array
    {
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('scramble:export', [
            '--path' => $this->path,
        ]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists($this->path);

        return json_decode(File::get($this->path), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $docs
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function resolveSchema(array $docs, array $schema): array
    {
        if (isset($schema['$ref'])) {
            $name = str_replace('#/components/schemas/', '', $schema['$ref']);

            return $this->resolveSchema($docs, $docs['components']['schemas'][$name]);
        }

        return $schema;
    }

    public function test_api_docs_can_be_exported(): void
    {
        // Act
        $docs = $this->exportApiDocs();

        // Assert
        $this->assertArrayHasKey('paths', $docs);
        $this->assertNotEmpty($docs['paths']);
    }

    public function test_paginated_endpoints_are_documented_with_pagination(): void
    {
        // Act
        $docs = $this->exportApiDocs();

        // Assert
        $tagsSchema = $docs['paths']['/v1/organizations/{organization}/tags']['get']['responses']['200']['content']['application/json']['schema'];
        $this->assertSame(['data', 'links', 'meta'], $tagsSchema['required']);
        $tagsData = $this->resolveSchema($docs, $tagsSchema['properties']['data']);
        $this->assertSame('array', $tagsData['type']);
        $this->assertSame('#/components/schemas/TagResource', $tagsData['items']['$ref']);

        $timeEntriesSchema = $docs['paths']['/v1/organizations/{organization}/time-entries']['get']['responses']['200']['content']['application/json']['schema'];
        $this->assertSame(['data', 'meta'], $timeEntriesSchema['required']);
        $this->assertSame(['total'], $timeEntriesSchema['properties']['meta']['required']);
    }

    public function test_fallback_routes_are_excluded_from_api_docs(): void
    {
        // Act
        $docs = $this->exportApiDocs();

        // Assert
        $this->assertArrayNotHasKey('/', $docs['paths']);
        $this->assertArrayNotHasKey('/{fallbackPlaceholder}', $docs['paths']);
    }

    public function test_fallback_routes_return_not_found(): void
    {
        // Act
        $rootResponse = $this->getJson('/api');
        $unknownResponse = $this->getJson('/api/does-not-exist');

        // Assert
        $rootResponse->assertNotFound();
        $rootResponse->assertJsonPath('message', 'API resource not found');
        $unknownResponse->assertNotFound();
        $unknownResponse->assertJsonPath('message', 'API resource not found');
    }
}
