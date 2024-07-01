<?php

declare(strict_types=1);

namespace App\Service\IpLookup;

interface IpLookupServiceContract
{
    public function lookup(string $ip): ?IpLookupResponseDto;
}
