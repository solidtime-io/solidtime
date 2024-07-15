<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class MovedToApiException extends HttpException
{
    public function __construct()
    {
        parent::__construct(403, 'Moved to API');
    }
}
