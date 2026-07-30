<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Tests the attachment downloads of the private disk via Laravel's storage.{disk} route,
 * configured with serveUsing/buildTemporaryUrlsUsing in AppServiceProvider::boot.
 * These tests use the real private disk, because Storage::fake would replace the disk
 * instance and thereby remove that configuration.
 */
class FileDownloadEndpointTest extends EndpointTestAbstract
{
    private function privateDisk(): Filesystem
    {
        return Storage::disk(config('filesystems.private'));
    }

    public function test_temporary_url_with_attachment_disposition_serves_file_as_attachment(): void
    {
        // Arrange
        $disk = $this->privateDisk();
        $disk->put('exports/test-attachment.csv', 'Description,Duration');
        $url = $disk->temporaryUrl('exports/test-attachment.csv', now()->addMinutes(5), [
            'ResponseContentDisposition' => 'attachment; filename="test-attachment.csv"',
        ]);

        // Act
        $response = $this->get($url);

        // Assert
        $response->assertOk();
        $response->assertDownload('test-attachment.csv');
        $disk->delete('exports/test-attachment.csv');
    }

    public function test_temporary_url_without_options_serves_file_inline(): void
    {
        // Arrange
        $disk = $this->privateDisk();
        $disk->put('exports/test-inline.csv', 'Description,Duration');
        $url = $disk->temporaryUrl('exports/test-inline.csv', now()->addMinutes(5));

        // Act
        $response = $this->get($url);

        // Assert
        $response->assertOk();
        $this->assertStringStartsWith('inline', (string) $response->headers->get('Content-Disposition'));
        $disk->delete('exports/test-inline.csv');
    }

    public function test_download_fails_with_tampered_disposition(): void
    {
        // Arrange
        $url = $this->privateDisk()->temporaryUrl('exports/test-tampered.csv', now()->addMinutes(5), [
            'ResponseContentDisposition' => 'attachment; filename="test-tampered.csv"',
        ]);

        // Act
        $response = $this->get(str_replace('attachment', 'inline', $url));

        // Assert
        $response->assertForbidden();
    }

    public function test_download_fails_with_expired_signature(): void
    {
        // Arrange
        $url = $this->privateDisk()->temporaryUrl('exports/test-expired.csv', now()->addMinutes(5), [
            'ResponseContentDisposition' => 'attachment; filename="test-expired.csv"',
        ]);
        $this->travel(6)->minutes();

        // Act
        $response = $this->get($url);

        // Assert
        $response->assertForbidden();
    }

    public function test_download_fails_if_file_does_not_exist(): void
    {
        // Arrange
        $url = $this->privateDisk()->temporaryUrl('exports/test-missing.csv', now()->addMinutes(5), [
            'ResponseContentDisposition' => 'attachment; filename="test-missing.csv"',
        ]);

        // Act
        $response = $this->get($url);

        // Assert
        $response->assertNotFound();
    }

    public function test_temporary_url_of_private_local_disk_points_to_storage_route_with_signed_disposition(): void
    {
        // Act
        $url = $this->privateDisk()->temporaryUrl('exports/test-export.pdf', now()->addMinutes(5), [
            'ResponseContentDisposition' => 'attachment; filename="test-export.pdf"',
        ]);

        // Assert
        $this->assertStringContainsString('/storage/exports/test-export.pdf', $url);
        $this->assertStringContainsString('disposition=attachment', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function test_temporary_url_of_s3_disk_includes_content_disposition_from_options(): void
    {
        // Arrange
        config([
            'filesystems.disks.s3.key' => 'test-key',
            'filesystems.disks.s3.secret' => 'test-secret',
            'filesystems.disks.s3.region' => 'us-east-1',
            'filesystems.disks.s3.bucket' => 'test-bucket',
        ]);

        // Act
        $url = Storage::disk('s3')->temporaryUrl('exports/test-export.pdf', now()->addMinutes(5), [
            'ResponseContentDisposition' => 'attachment; filename="test-export.pdf"',
        ]);

        // Assert
        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('attachment; filename="test-export.pdf"', $query['response-content-disposition'] ?? null);
        $this->assertArrayHasKey('X-Amz-Signature', $query);
    }
}
