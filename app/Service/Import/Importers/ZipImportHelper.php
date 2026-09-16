<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use ZipArchive;

/**
 * Extracts uploaded ZIP archives with limits on file count, total uncompressed
 * size and entry paths, so a small malicious archive can not fill the disk
 * (decompression bomb) or write outside the target directory (zip slip).
 */
class ZipImportHelper
{
    private const int CHUNK_SIZE = 1024 * 1024;

    /**
     * @throws ImportException
     */
    public function extract(string $zipPath, string $targetPath): void
    {
        $zip = new ZipArchive;
        $res = $zip->open($zipPath, ZipArchive::RDONLY);
        if ($res !== true) {
            throw new ImportException('Invalid ZIP, error code: '.$res);
        }

        try {
            $maxFiles = (int) config('import.zip_max_files');
            $maxUncompressedSize = (int) config('import.zip_max_uncompressed_size');

            if ($zip->numFiles > $maxFiles) {
                throw new ImportException('ZIP contains too many files, maximum is '.$maxFiles);
            }

            // Check the sizes declared in the archive before writing anything to disk
            $declaredSize = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                if ($stat === false) {
                    throw new ImportException('Invalid ZIP entry');
                }
                $this->validateEntryName($stat['name']);
                $declaredSize += $stat['size'];
                if ($declaredSize > $maxUncompressedSize) {
                    throw new ImportException('ZIP uncompressed size exceeds the maximum of '.$maxUncompressedSize.' bytes');
                }
            }

            // The declared sizes can be forged, so the written bytes are counted as well
            $writtenSize = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                if ($stat === false) {
                    throw new ImportException('Invalid ZIP entry');
                }
                $name = $stat['name'];
                $entryPath = $targetPath.DIRECTORY_SEPARATOR.$name;

                if (str_ends_with($name, '/')) {
                    $this->ensureDirectoryExists($entryPath);

                    continue;
                }
                $this->ensureDirectoryExists(dirname($entryPath));

                $stream = $zip->getStreamIndex($index);
                if ($stream === false) {
                    throw new ImportException('ZIP entry "'.$name.'" can not be read');
                }
                $target = fopen($entryPath, 'wb');
                if ($target === false) {
                    fclose($stream);
                    throw new ImportException('ZIP entry "'.$name.'" can not be extracted');
                }
                try {
                    while (! feof($stream)) {
                        $chunk = fread($stream, self::CHUNK_SIZE);
                        if ($chunk === false) {
                            throw new ImportException('ZIP entry "'.$name.'" can not be read');
                        }
                        $writtenSize += strlen($chunk);
                        if ($writtenSize > $maxUncompressedSize) {
                            throw new ImportException('ZIP uncompressed size exceeds the maximum of '.$maxUncompressedSize.' bytes');
                        }
                        fwrite($target, $chunk);
                    }
                } finally {
                    fclose($target);
                    fclose($stream);
                }
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @throws ImportException
     */
    private function validateEntryName(string $name): void
    {
        if ($name === '' || str_contains($name, "\0") || str_contains($name, '\\') || str_starts_with($name, '/')) {
            throw new ImportException('ZIP contains an invalid file path: "'.$name.'"');
        }
        if (preg_match('/^[a-zA-Z]:/', $name) === 1) {
            throw new ImportException('ZIP contains an invalid file path: "'.$name.'"');
        }
        foreach (explode('/', rtrim($name, '/')) as $segment) {
            if ($segment === '' || $segment === '..') {
                throw new ImportException('ZIP contains an invalid file path: "'.$name.'"');
            }
        }
    }

    /**
     * @throws ImportException
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (is_dir($path)) {
            return;
        }
        if (! mkdir($path, 0700, true) && ! is_dir($path)) {
            throw new ImportException('Directory "'.$path.'" can not be created');
        }
    }
}
