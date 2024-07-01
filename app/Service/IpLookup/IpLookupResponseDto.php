<?php

declare(strict_types=1);

namespace App\Service\IpLookup;

use App\Enums\Weekday;

class IpLookupResponseDto
{
    public ?string $timezone;

    public ?Weekday $startOfWeek;

    public ?string $currency;

    public function __construct(?string $timezone, ?Weekday $startOfWeek, ?string $currency)
    {
        $this->timezone = $timezone;
        $this->startOfWeek = $startOfWeek;
        $this->currency = $currency;
    }
}
