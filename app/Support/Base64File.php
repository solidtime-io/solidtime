<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\Mime\MimeTypes;

class Base64File
{
    /**
     * @return array{data: string, mime_type: string}|null
     */
    public static function decode(string $value): ?array
    {
        if (str_contains($value, ',')) {
            [, $value] = explode(',', $value, 2);
        }

        $value = preg_replace('/\s+/', '', $value);
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return null;
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($decoded);
        if ($mimeType === false) {
            return null;
        }

        return [
            'data' => $decoded,
            'mime_type' => $mimeType,
        ];
    }

    public static function extension(string $mimeType): ?string
    {
        return MimeTypes::getDefault()->getExtensions($mimeType)[0] ?? null;
    }
}
