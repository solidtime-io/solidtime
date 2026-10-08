<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\ExcludeAllRoutesFromDocs;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Fallback for unknown /api/* routes, to prevent a rendered HTML page
 */
#[ExcludeAllRoutesFromDocs]
class FallbackController extends Controller
{
    public function __invoke(): never
    {
        throw new NotFoundHttpException('API resource not found');
    }
}
