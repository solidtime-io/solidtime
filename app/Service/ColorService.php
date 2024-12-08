<?php

declare(strict_types=1);

namespace App\Service;

class ColorService
{
    /**
     * @var array<string>
     */
    private const array COLORS = [
        '#ef5350',
        '#ec407a',
        '#ab47bc',
        '#7e57c2',
        '#5c6bc0',
        '#42a5f5',
        '#29b6f6',
        '#26c6da',
        '#26a69a',
        '#66bb6a',
        '#9ccc65',
        '#d4e157',
        '#ffee58',
        '#ffca28',
        '#ffa726',
        '#ff7043',
        '#8d6e63',
        '#bdbdbd',
        '#78909c',
    ];

    private const string VALID_REGEX = '/^#[0-9a-f]{6}$/';

    public function getRandomColor(?string $seed = null): string
    {
        if ($seed !== null) {
            srand(crc32($seed));
        }

        return self::COLORS[array_rand(self::COLORS)];
    }

    public function isValid(string $color): bool
    {
        return preg_match(self::VALID_REGEX, $color) === 1;
    }
}
