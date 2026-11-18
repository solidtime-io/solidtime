<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Export;
use App\Models\Organization;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Export::class)]
class ExportModelTest extends ModelTestAbstract
{
    private function createExport(string $organizationName): Export
    {
        $organization = Organization::factory()->create([
            'name' => $organizationName,
        ]);

        return Export::factory()->forOrganization($organization)->completed()->create([
            'id' => '1f2470b2-19d9-4d45-8fc3-eb5c1e6040e6',
            'created_at' => Carbon::parse('2026-11-18 10:20:30', 'UTC'),
        ]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function organizationNameProvider(): iterable
    {
        yield 'simple name' => ['Acme GmbH', 'acme-gmbh'];
        yield 'umlauts and special characters' => ['Müller & Söhne GmbH', 'muller-sohne-gmbh'];
        yield 'emoji only name falls back' => ['🚀🚀', 'organization'];
        yield 'long name is truncated without trailing dash' => [
            'The Quite Long Organization Name Of Example Industries International',
            'the-quite-long-organization-name-of-exam',
        ];
        yield 'truncation at a dash is trimmed' => [
            str_repeat('a', 39).' bbb',
            str_repeat('a', 39),
        ];
    }

    #[DataProvider('organizationNameProvider')]
    public function test_download_filename_contains_slug_of_organization_name(string $organizationName, string $expectedSlug): void
    {
        // Arrange
        $export = $this->createExport($organizationName);

        // Act
        $filename = $export->getDownloadFilename('UTC');

        // Assert
        $this->assertSame('solidtime-export_'.$expectedSlug.'_2026-11-18_10-20_1f2470b2.zip', $filename);
    }

    public function test_download_filename_uses_date_in_given_timezone(): void
    {
        // Arrange
        $export = $this->createExport('Acme');
        $export->created_at = Carbon::parse('2026-11-18 23:30:00', 'UTC');

        // Act
        $filename = $export->getDownloadFilename('Europe/Vienna');

        // Assert
        $this->assertSame('solidtime-export_acme_2026-11-19_00-30_1f2470b2.zip', $filename);
        $this->assertSame('UTC', $export->created_at->getTimezone()->getName());
    }
}
