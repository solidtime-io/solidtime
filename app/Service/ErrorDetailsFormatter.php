<?php

declare(strict_types=1);

namespace App\Service;

use Illuminate\Support\Str;
use Throwable;

class ErrorDetailsFormatter
{
    private const int MAX_LENGTH = 50000;

    /**
     * Technical details of the exception and all previous exceptions for debugging in the admin panel.
     */
    public static function format(Throwable $exception): string
    {
        $parts = [];
        $current = $exception;
        while ($current !== null) {
            $parts[] = $current::class.': '.$current->getMessage()."\n".
                'at '.$current->getFile().':'.$current->getLine()."\n".
                $current->getTraceAsString();
            $current = $current->getPrevious();
        }

        return Str::limit(implode("\n\nCaused by: ", $parts), self::MAX_LENGTH);
    }
}
