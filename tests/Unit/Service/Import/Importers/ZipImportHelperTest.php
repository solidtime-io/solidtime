<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Service\Import\Importers\ImportException;
use App\Service\Import\Importers\ZipImportHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Tests\TestCase;
use ZipArchive;

#[CoversClass(ZipImportHelper::class)]
class ZipImportHelperTest extends TestCase
{
    private TemporaryDirectory $sourceDirectory;

    private TemporaryDirectory $targetDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sourceDirectory = TemporaryDirectory::make();
        $this->targetDirectory = TemporaryDirectory::make();
    }

    protected function tearDown(): void
    {
        $this->sourceDirectory->delete();
        $this->targetDirectory->delete();
        parent::tearDown();
    }

    /**
     * @param  array<string, string>  $files
     */
    private function createZip(array $files): string
    {
        $zipPath = $this->sourceDirectory->path('test.zip');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return $zipPath;
    }

    private function assertNothingExtracted(): void
    {
        $this->assertSame([], array_values(array_diff(scandir($this->targetDirectory->path()), ['.', '..'])));
    }

    public function test_extract_extracts_files_and_nested_directories(): void
    {
        // Arrange
        $zipPath = $this->createZip([
            'meta.json' => '{"version":"1.0"}',
            'nested/dir/file.csv' => 'a,b',
        ]);

        // Act
        app(ZipImportHelper::class)->extract($zipPath, $this->targetDirectory->path());

        // Assert
        $this->assertSame('{"version":"1.0"}', file_get_contents($this->targetDirectory->path('meta.json')));
        $this->assertSame('a,b', file_get_contents($this->targetDirectory->path('nested/dir/file.csv')));
    }

    public function test_extract_throws_exception_if_file_is_not_a_zip(): void
    {
        // Arrange
        $path = $this->sourceDirectory->path('not-a-zip.txt');
        file_put_contents($path, 'not a zip');

        // Act
        try {
            app(ZipImportHelper::class)->extract($path, $this->targetDirectory->path());
        } catch (ImportException $e) {
            // Assert
            $this->assertSame('Invalid ZIP, error code: 19', $e->getMessage());
            $this->assertNothingExtracted();

            return;
        }
        $this->fail();
    }

    public function test_extract_throws_exception_if_zip_contains_too_many_files(): void
    {
        // Arrange
        config(['import.zip_max_files' => 2]);
        $zipPath = $this->createZip([
            'a.txt' => 'a',
            'b.txt' => 'b',
            'c.txt' => 'c',
        ]);

        // Act
        try {
            app(ZipImportHelper::class)->extract($zipPath, $this->targetDirectory->path());
        } catch (ImportException $e) {
            // Assert
            $this->assertSame('ZIP contains too many files, maximum is 2', $e->getMessage());
            $this->assertNothingExtracted();

            return;
        }
        $this->fail();
    }

    public function test_extract_throws_exception_before_writing_if_declared_uncompressed_size_exceeds_limit(): void
    {
        // Arrange
        config(['import.zip_max_uncompressed_size' => 100]);
        $zipPath = $this->createZip([
            'a.txt' => str_repeat('a', 60),
            'b.txt' => str_repeat('b', 60),
        ]);

        // Act
        try {
            app(ZipImportHelper::class)->extract($zipPath, $this->targetDirectory->path());
        } catch (ImportException $e) {
            // Assert
            $this->assertSame('ZIP uncompressed size exceeds the maximum of 100 bytes', $e->getMessage());
            $this->assertNothingExtracted();

            return;
        }
        $this->fail();
    }

    public function test_extract_throws_exception_if_actual_uncompressed_size_exceeds_limit_despite_forged_headers(): void
    {
        // Arrange
        config(['import.zip_max_uncompressed_size' => 1000]);
        $zipPath = $this->createZip([
            'bomb.bin' => str_repeat("\0", 100000),
        ]);
        // Forge the uncompressed size in the local file header (offset 22) and central directory header (offset 24)
        $content = file_get_contents($zipPath);
        $forgedSize = pack('V', 10);
        $localHeaderOffset = strpos($content, "PK\x03\x04");
        $centralHeaderOffset = strpos($content, "PK\x01\x02");
        $this->assertNotFalse($localHeaderOffset);
        $this->assertNotFalse($centralHeaderOffset);
        $content = substr_replace($content, $forgedSize, $localHeaderOffset + 22, 4);
        $content = substr_replace($content, $forgedSize, $centralHeaderOffset + 24, 4);
        file_put_contents($zipPath, $content);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath, ZipArchive::RDONLY));
        $this->assertSame(10, $zip->statIndex(0)['size']);
        $zip->close();

        // Act
        try {
            app(ZipImportHelper::class)->extract($zipPath, $this->targetDirectory->path());
        } catch (ImportException $e) {
            // Assert
            $this->assertSame('ZIP uncompressed size exceeds the maximum of 1000 bytes', $e->getMessage());
            $extracted = $this->targetDirectory->path('bomb.bin');
            if (file_exists($extracted)) {
                $this->assertLessThanOrEqual(1000, filesize($extracted));
            }

            return;
        }
        $this->fail();
    }

    public function test_extract_throws_exception_if_zip_contains_path_traversal(): void
    {
        // Arrange
        $zipPath = $this->createZip([
            '../evil.txt' => 'evil',
        ]);

        // Act
        try {
            app(ZipImportHelper::class)->extract($zipPath, $this->targetDirectory->path());
        } catch (ImportException $e) {
            // Assert
            $this->assertSame('ZIP contains an invalid file path: "../evil.txt"', $e->getMessage());
            $this->assertFileDoesNotExist(dirname($this->targetDirectory->path()).'/evil.txt');
            $this->assertNothingExtracted();

            return;
        }
        $this->fail();
    }

    public function test_extract_throws_exception_if_zip_contains_nested_path_traversal(): void
    {
        // Arrange
        $zipPath = $this->createZip([
            'sub/../../evil.txt' => 'evil',
        ]);

        // Act
        try {
            app(ZipImportHelper::class)->extract($zipPath, $this->targetDirectory->path());
        } catch (ImportException $e) {
            // Assert
            $this->assertSame('ZIP contains an invalid file path: "sub/../../evil.txt"', $e->getMessage());
            $this->assertNothingExtracted();

            return;
        }
        $this->fail();
    }

    public function test_extract_throws_exception_if_zip_contains_absolute_path(): void
    {
        // Arrange
        $zipPath = $this->createZip([
            '/tmp/evil.txt' => 'evil',
        ]);

        // Act
        try {
            app(ZipImportHelper::class)->extract($zipPath, $this->targetDirectory->path());
        } catch (ImportException $e) {
            // Assert
            $this->assertSame('ZIP contains an invalid file path: "/tmp/evil.txt"', $e->getMessage());
            $this->assertNothingExtracted();

            return;
        }
        $this->fail();
    }

    public function test_extract_throws_exception_if_zip_contains_backslash_path(): void
    {
        // Arrange
        $zipPath = $this->createZip([
            '..\\evil.txt' => 'evil',
        ]);

        // Act
        try {
            app(ZipImportHelper::class)->extract($zipPath, $this->targetDirectory->path());
        } catch (ImportException $e) {
            // Assert
            $this->assertSame('ZIP contains an invalid file path: "..\\evil.txt"', $e->getMessage());
            $this->assertNothingExtracted();

            return;
        }
        $this->fail();
    }
}
